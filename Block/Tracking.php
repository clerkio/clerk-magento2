<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Tracking extends Template
{

    protected $formKey;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
    ) {
        parent::__construct($context);
        $this->formKey = $formKey;
    }
    /**
     * Get public key
     *
     * @return mixed
     */
    public function getPublicKey()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(
            Config::XML_PATH_PUBLIC_KEY,
            $scope,
            $scope_id
        );
    }

    public function getLanguage()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(
            Config::XML_PATH_LANGUAGE,
            $scope,
            $scope_id
        );
    }

    /**
     * Get collect emails
     *
     * @return string
     */
    public function getCollectionEmails()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return ($this->_scopeConfig->isSetFlag(
            Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS,
            $scope,
            $scope_id
        ) ? 'true' : 'false');
    }

    /**
     * Get collect carts
     *
     * @return string
     */
    public function getCollectionBaskets()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        $collectBaskets = "false";
        if($this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS, $scope, $scope_id) == '1'){
            $collectBaskets = "true";
        }
        return $collectBaskets;
    }

    public function getFormKey() {

        return $this->formKey->getFormKey();

    }
}
