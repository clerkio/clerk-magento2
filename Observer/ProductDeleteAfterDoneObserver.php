<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductDeleteAfterDoneObserver implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(ScopeConfigInterface $scopeConfig, Api $api)
    {
        $this->_scopeConfig = $scopeConfig;
        $this->api = $api;
    }

    /**
     * Remove product from Clerk
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED)) {
            $product = $observer->getProduct();

            if ($product && $product->getId()) {
                $this->api->removeProduct($product->getId());
            }
        }
    }
}