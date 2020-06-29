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
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_TEMPLATE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Determine if we should include categories in live search results
     *
     * @return bool
     */
    public function shouldIncludeCategories()
    {
        return ($this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_INCLUDE_CATEGORIES, ScopeInterface::SCOPE_STORE)) ? 'true' : 'false';
    }
    public function getSuggestions()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_SUGGESTIONS, ScopeInterface::SCOPE_STORE);
    }

    public function getCategories()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_CATEGORIES, ScopeInterface::SCOPE_STORE);
    }

    public function getPages()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_PAGES, ScopeInterface::SCOPE_STORE);
    }

    public function getPagesType()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_PAGES_TYPE, ScopeInterface::SCOPE_STORE);
    }
    public function getDropdownPosition()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_DROPDOWN_POSITION, ScopeInterface::SCOPE_STORE);
    }
    public function getInputSelector()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_INPUT_SELECTOR, ScopeInterface::SCOPE_STORE);
    }
    public function getFormSelector()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_FORM_SELECTOR, ScopeInterface::SCOPE_STORE);
    }
}
