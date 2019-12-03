<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;

class LiveSearch extends Template
{
    /**
     * Get live search template
     *
     * @return mixed
     */
    public function getLiveSearchTemplate()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_TEMPLATE);
    }

    /**
     * Determine if we should include categories in live search results
     *
     * @return bool
     */
    public function shouldIncludeCategories()
    {
        return ($this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_INCLUDE_CATEGORIES)) ? 'true' : 'false';
    }
    public function getSuggestions()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_SUGGESTIONS);
    }

    public function getCategories()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_CATEGORIES);
    }

    public function getPages()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_PAGES);
    }

    public function getPagesType()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_PAGES_TYPE);
    }
    public function getDropdownPosition()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_DROPDOWN_POSITION);
    }
}