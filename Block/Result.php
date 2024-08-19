<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogSearch\Block\Result as BaseResult;
use Magento\CatalogSearch\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\QueryFactory;

class Result extends BaseResult
{
    const TARGET_ID = 'clerk-search-results';

    public function __construct(
        ConfigHelper  $configHelper,
        Context       $context,
        LayerResolver $layerResolver,
        Data          $catalogSearchData,
        QueryFactory  $queryFactory,
        array         $data = []
    )
    {
        parent::__construct($context, $layerResolver, $catalogSearchData, $queryFactory, $data);
        $this->configHelper = $configHelper;
    }

    /**
     * @return mixed
     */
    public function getSuggestions()
    {
        return $this->configHelper->getValue(Config::XML_PATH_SEARCH_SUGGESTIONS);
    }

    /**
     * Get attributes for clerk span
     *
     * @return string
     */
    public function getSpanAttributes()
    {
        $output = '';

        $span_attributes = [
            'id' => 'clerk-search',
            'class' => 'clerk',
            'data-template' => '@' . $this->getSearchTemplate(),
            'data-query' => $this->getSearchQuery(),
            'data-target' => '#' . $this->getTargetId(),
            'data-offset' => 0,
            'data-after-render' => '_clerk_after_load_event',
        ];

        if ($this->shouldIncludeCategories()) {
            $span_attributes['data-search-categories'] = $this->getCategories();
            $span_attributes['data-search-pages'] = $this->getPages();
            $span_attributes['data-search-pages-type'] = $this->getPagesType();
        }


        if ($this->configHelper->getFlag(Config::XML_PATH_FACETED_SEARCH_ENABLED)) {
            try {
                $span_attributes['data-facets-target'] = "#clerk-search-filters";
                $span_attributes['data-facets-design'] = $this->getFacetsDesign();

                if ($titles = $this->configHelper->getValue(Config::XML_PATH_FACETED_SEARCH_TITLES)) {
                    $titles = json_decode($titles, true);

                    $titles_sorting = [];
                    foreach ($titles as $k => $v) {
                        if (array_key_exists('sort_order', $v)) {
                            $titles_sorting[$k] = $v['sort_order'];
                        }
                    }

                    asort($titles_sorting);

                    $span_attributes['data-facets-titles'] = json_encode(array_filter(array_combine(array_keys($titles), array_column($titles, 'label'))));
                    $span_attributes['data-facets-attributes'] = json_encode(array_keys($titles_sorting));

                    if ($multiselectAttributes = $this->configHelper->getValue(Config::XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES)) {
                        $span_attributes['data-facets-multiselect-attributes'] = '["' . str_replace(',', '","', $multiselectAttributes) . '"]';
                    }
                }
            } catch (Exception) {
                $span_attributes['data-facets-attributes'] = '["price","categories"]';
            }
        }

        foreach ($span_attributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        return trim($output);
    }

    /**
     * Get search template
     *
     * @return mixed
     */
    public function getSearchTemplate()
    {
        return $this->configHelper->getValue(Config::XML_PATH_SEARCH_TEMPLATE);
    }

    /**
     * Get the search query
     *
     * @return string
     */
    public function getSearchQuery()
    {
        return $this->catalogSearchData->getEscapedQueryText();
    }

    /**
     * Get HTML id of target
     *
     * @return string
     */
    public function getTargetId()
    {
        return self::TARGET_ID;
    }

    /**
     * Determine if we should include categories and pages in search results
     *
     * @return string
     *
     */
    public function shouldIncludeCategories()
    {
        return ($this->configHelper->getValue(Config::XML_PATH_SEARCH_INCLUDE_CATEGORIES)) ? 'true' : 'false';
    }

    /**
     * @return mixed
     */
    public function getCategories()
    {
        return $this->configHelper->getValue(Config::XML_PATH_SEARCH_CATEGORIES);
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->configHelper->getValue(Config::XML_PATH_SEARCH_PAGES);
    }

    /**
     * @return mixed
     */
    public function getPagesType()
    {
        return $this->configHelper->getValue(Config::XML_PATH_SEARCH_PAGES_TYPE);
    }

    /**
     * Get facets template
     *
     * @return mixed
     */
    public function getFacetsDesign()
    {
        return $this->configHelper->getValue(Config::XML_PATH_FACETED_SEARCH_DESIGN);
    }

    /**
     * Get no result text
     *
     * @return mixed
     */
    public function getNoResultsText()
    {
        return $this->configHelper->getValue(Config::XML_PATH_SEARCH_NO_RESULTS_TEXT);
    }

    /**
     * Get load more text
     *
     * @return mixed
     */
    public function getLoadMoreText()
    {
        return $this->configHelper->getValue(Config::XML_PATH_SEARCH_LOAD_MORE_TEXT);
    }
}
