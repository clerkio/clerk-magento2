<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class PowerstepScripts extends Template
{
    /**
     * Determine if we should show scripts
     *
     * @return bool
     */
    public function shouldShow()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_POWERSTEP_TYPE, ScopeInterface::SCOPE_STORE) == Config\Source\PowerstepType::TYPE_POPUP;
    }
}
