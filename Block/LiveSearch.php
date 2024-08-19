<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class LiveSearch extends Template
{

    /**
     * Get live search template
     *
     * @return mixed
     */
    public function getLiveSearchTemplate()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_TEMPLATE, $scope, $scope_id);
    }

    public function getShopBaseDomainUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Determine if we should include categories in live search results
     *
     * @return bool
     */
    public function shouldIncludeCategories()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return ($this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_INCLUDE_CATEGORIES, $scope, $scope_id)) ? 'true' : 'false';
    }
    public function getSuggestions()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_SUGGESTIONS, $scope, $scope_id);
    }

    public function getCategories()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_CATEGORIES, $scope, $scope_id);
    }

    public function getPages()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_PAGES, $scope, $scope_id);
    }

    public function getPagesType()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_PAGES_TYPE, $scope, $scope_id);
    }
    public function getDropdownPosition()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_DROPDOWN_POSITION, $scope, $scope_id);
    }
    public function getInputSelector()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_INPUT_SELECTOR, $scope, $scope_id);
    }
    public function getFormSelector()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_FORM_SELECTOR, $scope, $scope_id);
    }
}
