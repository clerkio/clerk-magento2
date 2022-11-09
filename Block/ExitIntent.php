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

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        $template_contents = $this->_scopeConfig->getValue(Config::XML_PATH_EXIT_INTENT_TEMPLATE, $scope, $scope_id);
        if($template_contents){
            $template_contents = explode(',', $template_contents);
        } else {
            $template_contents = [0 => ''];
        }

        return $template_contents;
    }
}
