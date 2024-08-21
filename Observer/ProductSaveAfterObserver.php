<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Adapter\Product as ProductAdapter;
use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductModelConfigurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped as ProductModelGrouped;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductSaveAfterObserver implements ObserverInterface
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
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

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
    /**
     * @var ClerkLogger
     */
    protected $clerkLogger;

    /**
     * ProductSaveAfterObserver constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param RequestInterface $request
     * @param Emulation $emulation
     * @param StoreManagerInterface $storeManager
     * @param Api $api
     * @param ProductAdapter $productAdapter
     * @param ProductModelConfigurable $productModelConfigurable
     * @param ProductModelGrouped $productModelGrouped
     * @param ProductRepositoryInterface $productRepository
     * @param ClerkLogger $clerkLogger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        RequestInterface $request,
        Emulation $emulation,
        StoreManagerInterface $storeManager,
        Api $api,
        ProductAdapter $productAdapter,
        ProductModelConfigurable $productModelConfigurable,
        ProductModelGrouped $productModelGrouped,
        ProductRepositoryInterface $productRepository,
        ClerkLogger              $clerkLogger
    ) {
        $this->scopeConfig       = $scopeConfig;
        $this->eventManager      = $eventManager;
        $this->request           = $request;
        $this->emulation         = $emulation;
        $this->storeManager      = $storeManager;
        $this->api               = $api;
        $this->productAdapter    = $productAdapter;
        $this->productRepository = $productRepository;
        $this->_productModelConfigurable = $productModelConfigurable;
        $this->_productModelGrouped      = $productModelGrouped;
        $this->clerkLogger         = $clerkLogger;
    }

    /**
     * Add product to Clerk
     *
     * @param  Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $product_store_ids = $product->getStoreIds();
        foreach ($product_store_ids as $store_id) {
            $product = $this->productRepository->getById($product->getId(), false, $store_id);
            if ($this->storeManager->getStore($store_id)->isActive()) {
                try {
                    $this->updateStore($product, $store_id);
                } finally {
                    $this->emulation->stopEnvironmentEmulation();
                }
            }
        }
    }

    /**
     * Update store with product data
     *
     * @param ProductModel|ProductInterface $product
     * @param int|string $store_id
     */
    protected function updateStore(Product $product, $store_id)
    {
        $this->emulation->startEnvironmentEmulation($store_id);

        if (!$this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED, ScopeInterface::SCOPE_STORE, $store_id)) {
            return;
        }
        if (!$product->getId()) {
            return;
        }
        // Cancel if product visibility is not as defined
        if ('any' != $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY, ScopeInterface::SCOPE_STORE, $store_id)) {
            if ($product->getVisibility() != $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY, ScopeInterface::SCOPE_STORE, $store_id)) {
                try {
                    $this->api->removeProduct($product->getId());
                } catch (Exception $e) {
                    return;
                }
            }
        }
        // Cancel if product is not saleable
        if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, ScopeInterface::SCOPE_STORE, $store_id)) {
            if (!$product->isSalable()) {
                try {
                    $this->api->removeProduct($product->getId());
                } catch (Exception $e) {
                    return;
                }
            }
        }

        $parent_product_ids = $this->_productModelConfigurable->getParentIdsByChild($product->getId());
        if (!empty($parent_product_ids)) {
            try {
                $parent_product = $this->productRepository->getById((int)$parent_product_ids[0], false, $store_id);
                $parent_product_data =
                    $this->productAdapter->getInfoForItem($parent_product, ScopeInterface::SCOPE_STORE, $store_id);
                $this->api->addProduct($parent_product_data, $store_id);
            } catch (NoSuchEntityException $e) {
                $this->clerkLogger->error('No parent products', ['warning' => $e->getMessage()]);
            }
        }

        $group_parent_product_ids = $this->_productModelGrouped->getParentIdsByChild($product->getId());
        if (!empty($group_parent_product_ids)) {
            foreach ($group_parent_product_ids as $group_parent_product_id) {
                try {
                    $group_parent_product =
                        $this->productRepository->getById((int)$group_parent_product_id, false, $store_id);
                    $group_parent_product_data =
                        $this->productAdapter->getInfoForItem($group_parent_product, ScopeInterface::SCOPE_STORE, $store_id);
                    $this->api->addProduct($group_parent_product_data, $store_id);
                } catch (NoSuchEntityException $e) {
                    $this->clerkLogger->error('No parent products', ['warning' => $e->getMessage()]);
                }
            }
        }
        $product_data = $this->productAdapter->getInfoForItem($product, 'store', $store_id);
        $this->api->addProduct($product_data, $store_id);
    }
}
