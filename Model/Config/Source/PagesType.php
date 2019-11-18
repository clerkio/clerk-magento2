<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PagesType implements ArrayInterface
{
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $PagesType = [
            ['value' => 'CMS Pages', 'label' => 'CMS Page']            
        ];

        return $PagesType;
    }
    
}