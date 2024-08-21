<?php

namespace Clerk\Clerk\Model\Config\Source;

use Clerk\Clerk\Helper\Context as ContextHelper;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;

class MultiselectFacetAttributes implements ArrayInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ContextHelper $contextHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->contextHelper = $contextHelper;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $res = [];

        foreach (self::toArray() as $_ => $value) {
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

        $attributes_defined = $attributes !== null;

        if (!$attributes_defined) {
            return [];
        }

        $values = [];

        foreach (explode(',', $attributes) as $attribute) {
            $values[$attribute] = str_replace(' ', '', $attribute);
        }

        return $values;
    }

    /**
     * Get configured facet attributes
     */
    private function getConfiguredAttributes()
    {
        $scope = $this->contextHelper->getScopeAdmin();
        $scope_id = $this->contextHelper->getScopeIdAdmin();
        return $this->scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, $scope, $scope_id);
    }
}
