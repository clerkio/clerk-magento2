<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class LoggerLevel implements ArrayInterface
{

    const LEVEL_ERROR = 'error';
    const LEVEL_WARN = 'warn';
    const LEVEL_ALL = 'all';
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
            self::LEVEL_WARN => __('Error + Warn'),
            self::LEVEL_ERROR => __('Only Error'),
            self::LEVEL_ALL => __('Error + Warn + Debug Mode')
        ];
    }
}