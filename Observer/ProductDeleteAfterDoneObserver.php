<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

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

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Api                  $api,
        RequestInterface     $request
    )
    {
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
        $_params = $this->request->getParams();
        $scope_id = 0;
        $scope = 'default';
        if (array_key_exists('store', $_params)) {
            $scope = 'store';
            $scope_id = $_params[$scope];
        }
        $product = $observer->getEvent()->getProduct();
        if ($product && $product->getId()) {
            if ($scope_id == 0) {
                $store_ids_prod = $product->getStoreIds();
                foreach ($store_ids_prod as $store_id) {
                    if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED, 'store', $store_id)) {
                        $this->api->removeProduct($product->getId(), $scope_id);
                    }
                }
            } else {
                if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED, $scope, $scope_id)) {
                    $this->api->removeProduct($product->getId(), $scope_id);
                }
            }
        }
    }
}
