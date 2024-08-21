<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\Config\Source\PowerstepType;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartAddProductCompleteObserver implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * CheckoutCartAddProductCompleteObserver constructor.
     *
     * @param ConfigHelper $configHelper
     * @param Session $checkoutSession
     */
    public function __construct(
        ConfigHelper $configHelper,
        Session $checkoutSession
    ) {
        $this->configHelper = $configHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Event observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->configHelper->getValue(Config::XML_PATH_POWERSTEP_TYPE) == PowerstepType::TYPE_POPUP) {
            $product = $observer->getProduct();

            $this->checkoutSession->setClerkShowPowerstep(true);
            $this->checkoutSession->setClerkProductId($product->getId());
        }
    }
}
