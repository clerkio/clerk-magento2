<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class CheckoutCartAddProductCompleteObserver implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * CheckoutCartAddProductCompleteObserver constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Session $checkoutSession)
    {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->scopeConfig->getValue(Config::XML_PATH_POWERSTEP_TYPE, ScopeInterface::SCOPE_STORE) == Config\Source\PowerstepType::TYPE_POPUP) {
            $product = $observer->getProduct();

            $this->checkoutSession->setClerkShowPowerstep(true);
            $this->checkoutSession->setClerkProductId($product->getId());
        }
    }

    /**
     * Determine if Clerk search is enabled
     *
     * @return bool
     */
//    private function isClerkSearchEnabled()
//    {
//        return $this->scopeConfig->isSetFlag(Config::XML_PATH_SEARCH_ENABLED);
//    }
}
