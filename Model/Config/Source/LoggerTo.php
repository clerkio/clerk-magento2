<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class LoggerTo implements ArrayInterface
{
    const TO_FILE = 'file';
    const TO_COLLECTION = 'collect';
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
            self::TO_COLLECTION => __('my.clerk.io'),
            self::TO_FILE => __('File')
        ];
    }
}