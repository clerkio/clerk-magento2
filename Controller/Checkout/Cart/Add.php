<?php

namespace Clerk\Clerk\Controller\Checkout\Cart;

use Clerk\Clerk\Model\Config;
use Magento\Checkout\Controller\Cart\Add as BaseAdd;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\ScopeInterface;

class Add extends BaseAdd
{
    /**
     * Get resolved back url
     *
     * @param null $defaultUrl
     *
     * @return mixed|null|string
     */
    protected function getBackUrl($defaultUrl = null)
    {

        $returnUrl = $this->getRequest()->getParam('return_url');
        if ($returnUrl && $this->_isInternalUrl($returnUrl)) {
            $this->messageManager->getMessages()->clear();
            return $returnUrl;
        }

        $shouldRedirectPowerstep = $this->_scopeConfig->getValue(
            Config::XML_PATH_POWERSTEP_ENABLED,
            ScopeInterface::SCOPE_STORE
        );

        $powerstepType = $this->_scopeConfig->getValue(
            Config::XML_PATH_POWERSTEP_TYPE,
            ScopeInterface::SCOPE_STORE
        );

        $productId = (int)$this->getRequest()->getParam('product');

        /**
         * Check if we should redirect to powerstep
         */
        if ($shouldRedirectPowerstep && $powerstepType == Config\Source\PowerstepType::TYPE_PAGE) {
            return $this->_url->getUrl('checkout/cart/added/id/' . $productId);
        }

        $shouldRedirectToCart = $this->_scopeConfig->getValue(
            'checkout/cart/redirect_to_cart',
            ScopeInterface::SCOPE_STORE
        );

        if ($shouldRedirectToCart || $this->getRequest()->getParam('in_cart')) {
            if ($this->getRequest()->getActionName() == 'add' && !$this->getRequest()->getParam('in_cart')) {
                $this->_checkoutSession->setContinueShoppingUrl($this->_redirect->getRefererUrl());
            }

            return $this->_url->getUrl('checkout/cart/added/id/' . $productId);
        }

        return $defaultUrl;

    }
}
