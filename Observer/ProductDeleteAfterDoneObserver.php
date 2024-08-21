<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class ProductDeleteAfterDoneObserver implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Api $api
     * @param RequestInterface $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Api                  $api,
        RequestInterface     $request
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->api = $api;
        $this->request = $request;
    }

    /**
     * Remove product from Clerk
     *
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $product_store_ids = $product->getStoreIds();
        foreach ($product_store_ids as $store_id) {
            if (!$this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED, ScopeInterface::SCOPE_STORE, $store_id)) {
                continue;
            }
            $this->api->removeProduct($product->getId(), $store_id);
        }
    }
}
