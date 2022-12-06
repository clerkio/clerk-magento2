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
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Clerk\Clerk\Model\Adapter\Product as ProductAdapter;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;

class ProductSaveAfterObserver implements ObserverInterface
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
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param RequestInterface $request
     * @param Emulation $emulation
     * @param StoreManagerInterface $storeManager
     * @param Api $api
     * @param ProductAdapter $productAdapter
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        RequestInterface $request,
        Emulation $emulation,
        StoreManagerInterface $storeManager,
        Api $api,
        ProductAdapter $productAdapter
    )
    {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->emulation = $emulation;
        $this->storeManager = $storeManager;
        $this->api = $api;
        $this->productAdapter = $productAdapter;
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
        $storeId = '0';
        $scope = 'default';
        if (array_key_exists('website', $_params)){
            $scope = 'website';
            $storeId = $_params[$scope];
        }
        if (array_key_exists('store', $_params)){
            $scope = 'store';
            $storeId = $_params[$scope];
        }
        $product = $observer->getEvent()->getProduct();
        if ($storeId == 0) {
            //Update all stores the product is connected to
            $productstoreIds = $product->getStoreIds();
            foreach ($productstoreIds as $productstoreId) {

                if ($this->storeManager->getStore($productstoreId)->isActive() == True) {
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

                 // 21-10-2021 KKY update parent products if in Grouped or child to Configurable before we check visibility and saleable - start

                 $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                 $confParentProductIds = $objectManager->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')->getParentIdsByChild($product->getId());
                 if(isset($confParentProductIds[0])){
                     $confparentproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($confParentProductIds[0]);

                     $productInfo = $this->productAdapter->getInfoForItem($confparentproduct, 'store', $storeId);
                     $this->api->addProduct($productInfo);

                 }
                 $groupParentProductIds = $objectManager->create('Magento\GroupedProduct\Model\Product\Type\Grouped')->getParentIdsByChild($product->getId());
                 if(isset($groupParentProductIds[0])){
                     foreach ($groupParentProductIds as $groupParentProductId) {
                         $groupparentproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($groupParentProductId);

                         $productInfo = $this->productAdapter->getInfoForItem($groupparentproduct, 'store', $storeId);
                         $this->api->addProduct($productInfo);

                     }
                 }

                 // 21-10-2021 KKY update parent products if in Grouped or child to Configurable - end

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

                $productInfo = $this->productAdapter->getInfoForItem($product, 'store', $storeId);

                $this->api->addProduct($productInfo);

            }
        }

    }
}
