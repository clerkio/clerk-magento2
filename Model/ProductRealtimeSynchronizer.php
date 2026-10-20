<?php

namespace Clerk\Clerk\Model;

use Clerk\Clerk\Model\Adapter\Product as ProductAdapter;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductModelConfigurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped as ProductModelGrouped;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductRealtimeSynchronizer
{
    /**
     * @var ProductModelGrouped
     */
    protected $_productModelGrouped;

    /**
     * @var ProductModelConfigurable
     */
    protected $_productModelConfigurable;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var ProductAdapter
     */
    protected $productAdapter;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Emulation $emulation,
        StoreManagerInterface $storeManager,
        Api $api,
        ProductAdapter $productAdapter,
        ProductModelConfigurable $productModelConfigurable,
        ProductModelGrouped $productModelGrouped,
        ProductRepositoryInterface $productRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->emulation = $emulation;
        $this->storeManager = $storeManager;
        $this->api = $api;
        $this->productAdapter = $productAdapter;
        $this->_productModelConfigurable = $productModelConfigurable;
        $this->_productModelGrouped = $productModelGrouped;
        $this->productRepository = $productRepository;
    }

    /**
     * Sync a product to Clerk. Store ID 0 updates every store the product is assigned to.
     *
     * @param ProductModel|ProductInterface $product
     * @param int|string $storeId
     * @return void
     */
    public function syncProduct($product, $storeId = 0)
    {
        if (!$product || !$product->getId()) {
            return;
        }

        if ($storeId == 0) {
            foreach ($product->getStoreIds() as $productStoreId) {
                try {
                    if (!$this->storeManager->getStore($productStoreId)->isActive()) {
                        continue;
                    }
                    $storeProduct = $this->productRepository->getById($product->getId(), false, $productStoreId, true);
                    $this->syncProductForStore($storeProduct, $productStoreId);
                } catch (NoSuchEntityException $e) {
                }
            }
            return;
        }

        $this->syncProductForStore($product, $storeId);
    }

    /**
     * Sync products by ID for a single store (order / credit memo).
     *
     * @param int[] $productIds
     * @param int|string $storeId
     * @return void
     */
    public function syncProductIds(array $productIds, $storeId)
    {
        $productIds = array_unique(array_filter($productIds));
        foreach ($productIds as $productId) {
            try {
                // Force reload: a cached instance still has the stock and salability from before the order.
                $product = $this->productRepository->getById($productId, false, $storeId, true);
                $this->syncProduct($product, $storeId);
            } catch (NoSuchEntityException $e) {
            }
        }
    }

    /**
     * @param ProductModel|ProductInterface $product
     * @param int|string $storeId
     * @return void
     */
    protected function syncProductForStore($product, $storeId)
    {
        $this->emulation->startEnvironmentEmulation($storeId);
        try {
            if (!$this->scopeConfig->getValue(
                Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )) {
                return;
            }

            if (!$product->getId()) {
                return;
            }

            // Parents first: a hidden, disabled or sold-out child still changes its parent's price and stock.
            foreach ($this->getParentIds($product) as $parentId) {
                try {
                    $parent = $this->productRepository->getById($parentId, false, $storeId, true);
                    $this->pushProduct($parent, $storeId);
                } catch (NoSuchEntityException $e) {
                }
            }

            $this->pushProduct($product, $storeId);
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
    }

    /**
     * Add the product to Clerk, or remove it when it should no longer be in the feed.
     *
     * @param ProductModel|ProductInterface $product
     * @param int|string $storeId
     * @return void
     */
    protected function pushProduct($product, $storeId)
    {
        if ($product->getStatus() == Status::STATUS_DISABLED) {
            $this->api->removeProduct($product->getId(), $storeId);
            return;
        }

        $visibility = $this->scopeConfig->getValue(
            Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ('any' != $visibility && $product->getVisibility() != $visibility) {
            return;
        }

        if ($this->scopeConfig->getValue(
            Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            if (!$product->isSalable()) {
                // The feed leaves unsalable products out, so a sold-out product must leave Clerk too.
                $this->api->removeProduct($product->getId(), $storeId);
                return;
            }
        }

        $this->api->addProduct(
            $this->productAdapter->getInfoForItem($product, 'store', $storeId),
            $storeId
        );
    }

    /**
     * @param ProductModel|ProductInterface $product
     * @return int[]
     */
    protected function getParentIds($product)
    {
        return array_unique(array_merge(
            $this->_productModelConfigurable->getParentIdsByChild($product->getId()),
            $this->_productModelGrouped->getParentIdsByChild($product->getId())
        ));
    }
}
