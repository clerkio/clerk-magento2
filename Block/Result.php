<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\CatalogSearch\Block\Result as BaseResult;

class Result extends BaseResult
{
    const TARGET_ID = 'clerk-search-results';

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

    /**
     * Get attributes for clerk span
     *
     * @return string
     */
    public function getSpanAttributes()
    {
        $output = '';

        $spanAttributes = [
            'id' => 'clerk-search',
            'class' => 'clerk',
            'data-template' => '@' . $this->getSearchTemplate(),
            'data-query' => $this->getSearchQuery(),
            'data-target' => '#' . $this->getTargetId(),
            'data-offset' => 0,
            'data-after-render' => '_clerk_after_load_event',
        ];

        if ($this->_scopeConfig->isSetFlag(Config::XML_PATH_FACETED_SEARCH_ENABLED)) {
            if ($attributes = $this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES)) {
                $spanAttributes['data-facets-target'] = "#clerk-search-filters";
                $spanAttributes['data-facets-attributes'] = '["' . str_replace(',', '","', $attributes) . '"]';

                if ($multiselectAttributes = $this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES)) {
                    $spanAttributes['data-facets-multiselect-attributes'] = '["' . str_replace(',', '","', $multiselectAttributes) . '"]';
                }

                if ($titles = $this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_TITLES)) {
                    $spanAttributes['data-facets-titles'] = $titles;
                }
            }
        }


        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        return trim($output);
    }

    /**
     * Get html id of target
     *
     * @return string
     */
    public function getTargetId()
    {
        return self::TARGET_ID;
    }

    /**
     * Get no results text
     *
     * @return mixed
     */
    public function getNoResultsText()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_NO_RESULTS_TEXT);
    }

    /**
     * Get load more text
     *
     * @return mixed
     */
    public function getLoadMoreText()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_LOAD_MORE_TEXT);
    }
}