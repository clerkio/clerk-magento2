<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;

class Tracking extends Template
{
    /**
     * Get public key
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->_scopeConfig->getValue(
            Config::XML_PATH_PUBLIC_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get collect emails
     *
     * @return string
     */
    public function getCollectionEmails()
    {
        return ($this->_scopeConfig->isSetFlag(
            Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ? 'true' : 'false');
    }
}