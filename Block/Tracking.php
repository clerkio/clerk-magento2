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
        return $this->_scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}