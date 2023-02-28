<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Clerk\Clerk\Model\Adapter\Product as ProductAdapter;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductModelConfigurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped as ProductModelGrouped;
use Magento\Catalog\Model\Product as ProductModel;

class ProductSaveAfterObserver implements ObserverInterface
{
    /**
     * @var ProductModel
     */
    protected $_productModel;

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
     * ProductSaveAfterObserver constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param RequestInterface $request
     * @param Emulation $emulation
     * @param StoreManagerInterface $storeManager
     * @param Api $api
     * @param ProductAdapter $productAdapter
     * @param ProductModelConfigurable $productModelConfigurable
     * @param ProductModelGrouped $productModelGrouped
     * @param ProductModel $productModel
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
        ProductModel $productModel
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->emulation = $emulation;
        $this->storeManager = $storeManager;
        $this->api = $api;
        $this->productAdapter = $productAdapter;
        $this->_productModelConfigurable = $productModelConfigurable;
        $this->_productModelGrouped = $productModelGrouped;
        $this->_productModel = $productModel;
    }

    /**
     * Add product to Clerk
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $_params = $this->request->getParams();
        $storeId = 0;
        $scope = 'default';
        if (array_key_exists('store', $_params)){
            $scope = 'store';
            $storeId = $_params[$scope];
        }
        $product = $observer->getEvent()->getProduct();
        if ($storeId == 0) {
            //Update all stores the product is connected to
            $productstoreIds = $product->getStoreIds();
            foreach ($productstoreIds as $productstoreId) {

                if ($this->storeManager->getStore($productstoreId)->isActive() == true) {
                    try {
                        $this->updateStore($product, $productstoreId);
                    } finally {
                        $this->emulation->stopEnvironmentEmulation();
                    }
                }
            }
        } else {
            //Update single store
            try {
                $this->updateStore($product, $storeId);
            } finally {
                $this->emulation->stopEnvironmentEmulation();
            }
        }
    }

    /**
     * @param $storeId
     */
    protected function updateStore(Product $product, $storeId)
    {
        $this->emulation->startEnvironmentEmulation($storeId);
        if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED, ScopeInterface::SCOPE_STORE, $storeId)) {
            if ($product->getId()) {

                //Cancel if product visibility is not as defined
                if ($product->getVisibility() != $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY, ScopeInterface::SCOPE_STORE, $storeId)) {
                    return;
                }

                //Cancel if product is not saleable
                if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, ScopeInterface::SCOPE_STORE, $storeId)) {
                    if (!$product->isSalable()) {
                        return;
                    }
                }

                // 21-10-2021 KKY update parent products if in Grouped or child to Configurable before we check visibility and saleable - start

                $confParentProductIds = $this->_productModelConfigurable->getParentIdsByChild($product->getId());
                if (isset($confParentProductIds[0])) {
                    $confparentproduct = $this->_productModel->load($confParentProductIds[0]);

                    $productInfo = $this->productAdapter->getInfoForItem($confparentproduct, 'store', $storeId);
                    $this->api->addProduct($productInfo, $storeId);

                }
                $groupParentProductIds = $this->_productModelGrouped->getParentIdsByChild($product->getId());
                if (isset($groupParentProductIds[0])) {
                    foreach ($groupParentProductIds as $groupParentProductId) {
                        $groupparentproduct = $this->_productModel->load($groupParentProductId);

                        $productInfo = $this->productAdapter->getInfoForItem($groupparentproduct, 'store', $storeId);
                        $this->api->addProduct($productInfo, $storeId);

                    }
                }

                // 21-10-2021 KKY update parent products if in Grouped or child to Configurable - end

                $productInfo = $this->productAdapter->getInfoForItem($product, 'store', $storeId);

                $this->api->addProduct($productInfo, $storeId);

            }
        }
    }
}
