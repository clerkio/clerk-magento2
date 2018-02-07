<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PowerstepType implements ArrayInterface
{
    const TYPE_PAGE = 1;
    const TYPE_POPUP = 2;

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
            self::TYPE_PAGE => __('Page'),
            self::TYPE_POPUP => __('Popup')
        ];
    }
}