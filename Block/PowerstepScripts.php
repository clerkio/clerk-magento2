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
        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }
        return $this->_scopeConfig->getValue(Config::XML_PATH_POWERSTEP_TYPE, $scope, $scope_id) == Config\Source\PowerstepType::TYPE_POPUP;
    }
}
