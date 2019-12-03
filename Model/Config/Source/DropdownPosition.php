<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class DropDownPosition implements ArrayInterface
{
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $Positions= [
            ['value' => 'left', 'label' => 'Left'],
            ['value' => 'center', 'label' => 'Center'],
            ['value' => 'right', 'label' => 'Right'],
            ['value' => 'below', 'label' => 'Below'],
            ['value' => 'off', 'label' => 'Off']
        ];

        return $Positions;
    }
    
}