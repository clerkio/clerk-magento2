<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class LiveSearch extends Template
{
    /**
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        ConfigHelper     $configHelper,
        Template\Context $context,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->configHelper = $configHelper;
    }

    /**
     * Get live search template
     *
     * @return mixed
     */
    public function getLiveSearchTemplate()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_TEMPLATE);
    }

    /**
     * Get shop base domain url
     *
     * @return string
     */
    public function getShopBaseDomainUrl()
    {
        return $this->configHelper->getBaseUrl();
    }

    /**
     * Determine if we should include categories in live search results
     *
     * @return string
     */
    public function shouldIncludeCategories()
    {
        return ($this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_INCLUDE_CATEGORIES)) ? 'true' : 'false';
    }

    /**
     * Get number of suggestions
     *
     * @return mixed
     */
    public function getSuggestions()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_SUGGESTIONS);
    }

    /**
     * Get number of categories
     *
     * @return mixed
     */
    public function getCategories()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_CATEGORIES);
    }

    /**
     * Get number of pages
     *
     * @return mixed
     */
    public function getPages()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_PAGES);
    }

    /**
     * Get search pages type
     *
     * @return mixed
     */
    public function getPagesType()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_PAGES_TYPE);
    }

    /**
     * Get injection position
     *
     * @return mixed
     */
    public function getDropdownPosition()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_DROPDOWN_POSITION);
    }

    /**
     * Get live-search css selector
     *
     * @return mixed
     */
    public function getInputSelector()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_INPUT_SELECTOR);
    }

    /**
     * Get form element css selector
     *
     * @return mixed
     */
    public function getFormSelector()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_FORM_SELECTOR);
    }
}
