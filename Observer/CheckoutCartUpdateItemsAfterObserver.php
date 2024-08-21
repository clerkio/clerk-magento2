<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Helper\Context as ContextHelper;
use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartUpdateItemsAfterObserver implements ObserverInterface
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Cart
     */
    protected $cart;
    /**
     * @var string
     */
    protected $endpoint;
    /**
     * @var Api
     */
    protected $api;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;
    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    /**
     * CheckoutCartUpdateItemsAfterObserver constructor.
     *
     * @param Cart $cart
     * @param CustomerSession $customerSession
     * @param Api $api
     * @param ConfigHelper $configHelper
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        Cart $cart,
        CustomerSession $customerSession,
        Api $api,
        ConfigHelper $configHelper,
        ContextHelper $contextHelper
    ) {
        $this->customerSession = $customerSession;
        $this->cart            = $cart;
        $this->api = $api;
        $this->configHelper = $configHelper;
        $this->contextHelper = $contextHelper;
    }

    /**
     * Event observer
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS)
            || !$this->configHelper->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS)
            || !$this->customerSession->isLoggedIn()) {
            return;
        }
        $cart_product_ids = [];
        foreach ($this->cart->getQuote()->getAllVisibleItems() as $item) {
            if (!in_array($item->getProductId(), $cart_product_ids)) {
                $cart_product_ids[] = $item->getProductId();
            }
        }
        $email = $this->customerSession->getCustomer()->getEmail();
        try {
            $this->api->logBasket($cart_product_ids, $email, $this->contextHelper->getStoreId());
        } catch (Exception $e) {
            return;
        }
    }
}
