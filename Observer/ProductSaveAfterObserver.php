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
        $storeId = $this->request->getParam('store', 0);
        $product = $observer->getEvent()->getProduct();
        if ($storeId == 0) {
            //Update all stores
            foreach ($this->storeManager->getStores() as $store) {
                $this->updateStore($product, $store->getId());
            }
        } else {
            //Update single store
            $this->updateStore($product, $storeId);
        }
    }

    /**
     * @param $storeId
     */
    protected function updateStore(Product $product, $storeId)
    {
        $this->emulation->startEnvironmentEmulation($storeId);
        if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED, ScopeInterface::SCOPE_STORE)) {
            if ($product->getId()) {

                //Cancel if product visibility is not as defined
                if ($product->getVisibility() != $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY, ScopeInterface::SCOPE_STORE)) {
                    return;
                }

                //Cancel if product is not saleable
                if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, ScopeInterface::SCOPE_STORE)) {
                    if (!$product->isSalable()) {
                        return;
                    }
                }

                $productInfo = $this->productAdapter->getInfoForItem($product);

                $this->api->addProduct($productInfo);
            }
        }

        $this->emulation->stopEnvironmentEmulation();
    }
}
