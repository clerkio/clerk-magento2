<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class ExitIntent extends Template
{
    /**
     * Get exit intent template
     *
     * @return mixed
     */
    public function getExitIntentTemplate()
    {
        return explode(',',$this->_scopeConfig->getValue(Config::XML_PATH_EXIT_INTENT_TEMPLATE, ScopeInterface::SCOPE_STORE));
    }
}
