<?php

namespace Clerk\Clerk\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class FacetTitles extends Value
{
    /**
     * JSON Encode value
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->setValue(json_encode(array_filter((array) $this->getValue())));
        return $this;
    }
}
