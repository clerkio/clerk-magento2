<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;

class CheckoutCartUpdateItemsAfterObserver implements ObserverInterface
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
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * CheckoutCartUpdateItemsAfterObserver constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Session $checkoutSession, Cart $cart, CustomerSession $customerSession)
    {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->cart = $cart;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->scopeConfig->getValue('clerk/product_synchronization/collect_baskets', ScopeInterface::SCOPE_STORE) == '1') {
            $cart_productIds = [];
            foreach ($this->cart->getQuote()->getAllVisibleItems() as $item){
                if (!in_array($item->getProductId(), $cart_productIds)) {
                    array_push($cart_productIds, $item->getProductId());
                }
            }

            if($this->customerSession->isLoggedIn()) {
                $Endpoint = 'https://api.clerk.io/v2/log/basket/set';

                $data_string = json_encode([
                    'key' => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE),
                    'products' => $cart_productIds,
                    'email' => $this->customerSession->getCustomer()->getEmail()]);

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $Endpoint);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
                curl_exec($curl);

            }
        }
    }

}
