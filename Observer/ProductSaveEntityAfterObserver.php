<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\Handler\Image;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * @var Image
     */
    protected $imageHandler;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        Api $api,
        Emulation $emulation,
        Image $imageHandler,
        StoreManagerInterface $storeManager
    )
    {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->api = $api;
        $this->emulation = $emulation;
        $this->imageHandler = $imageHandler;
        $this->storeManager = $storeManager;
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
                    return;
                }

                //Cancel if product is not saleable
                if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY)) {
                    if (!$product->isSalable()) {
                        return;
                    }
                }

                $store = $this->storeManager->getDefaultStoreView();
                $this->emulation->startEnvironmentEmulation($store->getId());

                $imageUrl = $this->imageHandler->handle($product);

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

                $this->emulation->stopEnvironmentEmulation();
            }
        }
    }
}