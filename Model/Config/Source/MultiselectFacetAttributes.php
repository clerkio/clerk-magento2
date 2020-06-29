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

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
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
        return $this->scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, ScopeInterface::SCOPE_STORE);
    }
}
