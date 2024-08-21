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
        return [
            ['value' => 'cms page', 'label' => 'CMS Page'],
            ['value' => 'all', 'label' => 'All']
        ];
    }
}
