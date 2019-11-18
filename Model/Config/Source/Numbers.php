<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Numbers implements ArrayInterface
{
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $Numbers = [
            ['value' => '0', 'label' => 'Don\'t show'],
            ['value' => '1', 'label' => '1'],
            ['value' => '2', 'label' => '2'],
            ['value' => '3', 'label' => '3'],
            ['value' => '4', 'label' => '4'],
            ['value' => '5', 'label' => '5'],
            ['value' => '6', 'label' => '6'],
            ['value' => '7', 'label' => '7'],
            ['value' => '8', 'label' => '8'],
            ['value' => '9', 'label' => '9'],
            ['value' => '10', 'label' => '10']
        ];

        return $Numbers;
    }
    
}