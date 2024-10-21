<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\CatalogSearch\Block\Result as BaseResult;
use Magento\Store\Model\ScopeInterface;

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

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_TEMPLATE, $scope, $scope_id);
    }

    /**
     * Get facets template
     *
     * @return mixed
     */
    public function getFacetsDesign()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_DESIGN, $scope, $scope_id);
    }

    /**
     * Determine if we should include categories and pages in search results
     *
     * @return bool
     *
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

        return ($this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_INCLUDE_CATEGORIES, $scope, $scope_id)) ? 'true' : 'false';
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

        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_SUGGESTIONS, $scope, $scope_id);
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

        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_CATEGORIES, $scope, $scope_id);
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

        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_PAGES, $scope, $scope_id);
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

        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_PAGES_TYPE, $scope, $scope_id);
    }


    /**
     * Get attributes for clerk span
     *
     * @return string
     */
    public function getSpanAttributes()
    {
        $output = '';

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        $spanAttributes = [
            'id' => 'clerk-search',
            'class' => 'clerk',
            'data-template' => '@' . $this->getSearchTemplate(),
            'data-query' => $this->getSearchQuery(),
            'data-target' => '#' . $this->getTargetId(),
            'data-offset' => 0,
            'data-after-render' => '_clerk_after_load_event',
        ];
        if($this->_scopeConfig->isSetFlag(Config::XML_PATH_FACETS_IN_URL, $scope, $scope_id)){
            $spanAttributes['data-facets-in-url'] = "true";
        }

        if ($this->shouldIncludeCategories()) {
            $spanAttributes['data-search-categories'] = $this->getCategories();
            $spanAttributes['data-search-pages'] = $this->getPages();
            $spanAttributes['data-search-pages-type'] = $this->getPagesType();
        }


        if ($this->_scopeConfig->isSetFlag(Config::XML_PATH_FACETED_SEARCH_ENABLED, $scope, $scope_id)) {
            try {
                $spanAttributes['data-facets-target'] = "#clerk-search-filters";
                $spanAttributes['data-facets-design'] =  $this->getFacetsDesign();

                if ($titles = $this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_TITLES, $scope, $scope_id)) {
                    $titles = json_decode($titles, true);

                    $titles_sorting = [];
                    foreach ($titles as $k => $v) {
                        if (array_key_exists('sort_order', $v)) {
                            $titles_sorting[$k] = $v['sort_order'];
                        }
                    }

                    asort($titles_sorting);

                    $spanAttributes['data-facets-titles'] = json_encode(array_filter(array_combine(array_keys($titles), array_column($titles, 'label'))));
                    $spanAttributes['data-facets-attributes'] = json_encode(array_keys($titles_sorting));

                    if ($multiselectAttributes = $this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES, $scope, $scope_id)) {
                        $spanAttributes['data-facets-multiselect-attributes'] = '["' . str_replace(',', '","', $multiselectAttributes) . '"]';
                    }
                }
            } catch (\Exception $e) {
                $spanAttributes['data-facets-attributes'] = '["price","categories"]';
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

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_NO_RESULTS_TEXT, $scope, $scope_id);
    }

    /**
     * Get load more text
     *
     * @return mixed
     */
    public function getLoadMoreText()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_SEARCH_LOAD_MORE_TEXT, $scope, $scope_id);
    }
}
