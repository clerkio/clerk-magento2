<?php

namespace Clerk\Clerk\Model\Config\Source;

use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Model\ScopeInterface;

class MultiselectFacetAttributes implements ArrayInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\RequestInterface $requestInterface
        )
    {
        $this->scopeConfig = $scopeConfig;
        $this->requestInterface = $requestInterface;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $res = [];

        foreach (self::toArray() as $index => $value) {
            $res[] = ['value' => $value, 'label' => $value];
        }

        return $res;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getConfiguredAttributes();

	    $attributes_defined = is_null($attributes) ? false : true;

	    if(!$attributes_defined){
		    return [];
	    }

        $values = [];

        foreach (explode(',', $attributes) as $attribute) {
            $values[$attribute] = str_replace(' ','', $attribute);
        }

        return $values;
    }

    /**
     * Get configured facet attributes
     */
    private function getConfiguredAttributes()
    {
        $_params = $this->requestInterface->getParams();
        $scope_id = '0';
        $scope = 'default';
        if (array_key_exists('website', $_params)){
            $scope = 'website';
            $scope_id = $_params[$scope];
        }
        if (array_key_exists('store', $_params)){
            $scope = 'store';
            $scope_id = $_params[$scope];
        }
        return $this->scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, $scope, $scope_id);
    }
}
