<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;

class LiveSearch extends Template
{
    public function __construct(
        ConfigHelper     $configHelper,
        Template\Context $context,
        array            $data = []
    )
    {
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
     * @return mixed
     */
    public function getSuggestions()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_SUGGESTIONS);
    }

    /**
     * @return mixed
     */
    public function getCategories()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_CATEGORIES);
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_PAGES);
    }

    /**
     * @return mixed
     */
    public function getPagesType()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_PAGES_TYPE);
    }

    /**
     * @return mixed
     */
    public function getDropdownPosition()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_DROPDOWN_POSITION);
    }

    /**
     * @return mixed
     */
    public function getInputSelector()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_INPUT_SELECTOR);
    }

    /**
     * @return mixed
     */
    public function getFormSelector()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LIVESEARCH_FORM_SELECTOR);
    }
}
