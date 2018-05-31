<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;

class ProductSaveEntityAfterObserver implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Stock
     */
    protected $stockHelper;

    /**
     * @var StockRegistryStorage
     */
    protected $stockRegistryStorage;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        Stock $stockHelper,
        StockRegistryStorage $stockRegistryStorage,
        ManagerInterface $eventManager,
        Api $api
    )
    {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->stockHelper = $stockHelper;
        $this->stockRegistryStorage = $stockRegistryStorage;
        $this->eventManager = $eventManager;
        $this->api = $api;
    }

    /**
     * Add product to Clerk
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED)) {
            /** @var Product $product */
            $product = $observer->getProduct();

            if ($product && $product->getId()) {

                //Cancel if product visibility is not as defined
                if ($product->getVisibility() != $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY)) {
                    $this->api->removeProduct($product->getId());
                    return;
                }

                //Cancel if product is not saleable
                if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY)) {
                    if (!$this->isSalable($product)) {
                        $this->api->removeProduct($product->getId());
                        return;
                    }
                }

                $store = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
                $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();

                $productItem = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'price' => $product->getFinalPrice(),
                    'list_price' => $product->getPrice(),
                    'image' => $imageUrl,
                    'url' => $product->getUrlModel()->getUrl($product),
                    'categories' => $product->getCategoryIds(),
                    'sku' => $product->getSku(),
                    'on_sale' => ($product->getFinalPrice() < $product->getPrice()),
                ];

                /**
                 * @todo Refactor to use fieldhandlers or similar
                 */
                $configFields = $this->scopeConfig->getValue(
                    Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS
                );

                $fields = explode(',', $configFields);

                foreach ($fields as $field) {
                    if (! isset($productItem[$field])) {
                        $productItem[$field] = $product->getData($field);
                    }
                }

                $productObject = new \Magento\Framework\DataObject();
                $productObject->setData($productItem);

                $this->eventManager->dispatch('clerk_product_sync_before', ['product' => $productObject]);

                $this->api->addProduct($productObject->toArray());
            }
        }
    }

    /**
     * Checks if product is salable
     *
     * Works around problems with cached
     *
     * @param Product $product
     * @return bool
     */
    public function isSalable(Product $product)
    {
        $productId = $product->getId();

        // isSalable relies on status that is assigned after initial product load
        // stock registry holds cached old stock status, invalidate to force reload
        $this->stockRegistryStorage->removeStockStatus($productId);
        $this->stockHelper->assignStatusToProduct($product);

        return $product->isSalable();
    }
}
