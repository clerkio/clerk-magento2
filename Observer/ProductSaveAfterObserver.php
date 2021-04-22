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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Clerk\Clerk\Model\Adapter\Product as ProductAdapter;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

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
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

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
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        RequestInterface $request,
        Emulation $emulation,
        StoreManagerInterface $storeManager,
        Api $api,
        ProductAdapter $productAdapter,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
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
        $this->productRepository = $productRepository;
        $this->logger = $logger;
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
                try {
                    /**
                     * fetch correct product model for the store
                     * @var Product $product
                     */
                    $product = $this->productRepository->getById($product->getId(), false, $store->getId());
                    $this->updateStore($product, $store->getId());
                } catch (NoSuchEntityException $e) {
                    $this->logger->error($e->getMessage());
                }
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
                    $this->emulation->stopEnvironmentEmulation();
                    return;
                }

                //Cancel if product is not saleable
                if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, ScopeInterface::SCOPE_STORE)) {
                    if (!$product->isSalable()) {
                        $this->emulation->stopEnvironmentEmulation();
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
