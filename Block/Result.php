<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\CatalogSearch\Block\Result as BaseResult;

class Result extends BaseResult
{
    /**
     * Get search query
     *
     * @return string
     */
    public function getSearchQuery()
    {
        return $this->catalogSearchData->getEscapedQueryText();
    }

    /**
     * Get search template
     *
     * @return mixed
     */
    public function getSearchTemplate()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_TEMPLATE);
    }
}