<?php

namespace Clerk\Clerk\Model\Indexer\Product\Action;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\Handler\Image;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class Rows
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
     * @var ProductRepository
     */
    protected $productRepository;

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

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param ProductRepository $productRepository
     * @param Api $api
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        Stock $stockHelper,
        StockRegistryStorage $stockRegistryStorage,
        ManagerInterface $eventManager,
        ProductRepository $productRepository,
        Api $api,
        Emulation $emulation,
        Image $imageHandler,
        StoreManagerInterface $storeManager
    ) {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->stockHelper = $stockHelper;
        $this->stockRegistryStorage = $stockRegistryStorage;
        $this->eventManager = $eventManager;
        $this->productRepository = $productRepository;
        $this->api = $api;
        $this->emulation = $emulation;
        $this->imageHandler = $imageHandler;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute action for given ids
     *
     * @param array|int $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function execute($ids = null)
    {
        if (!$this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED)) {
            return;
        }

        if (!isset($ids) || empty($ids)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t rebuild the index for an undefined product.')
            );
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        foreach ($ids as $id) {
            $this->reindexRow($id);
        }
    }

    /**
     * Refresh entity index
     *
     * @param int $productId
     * @return void
     */
    protected function reindexRow($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $this->api->removeProduct($productId);
            return;
        }

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
