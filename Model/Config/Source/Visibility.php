<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Catalog\Model\Product\Visibility as BaseVisibility;

class Visibility implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $res = [];

        foreach (self::toArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
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
        return [
            BaseVisibility::VISIBILITY_IN_CATALOG => __('Catalog'),
            BaseVisibility::VISIBILITY_IN_SEARCH => __('Search'),
            BaseVisibility::VISIBILITY_BOTH => __('Catalog, Search')
        ];
    }
}