<?php

namespace Clerk\Clerk\Model\Config\Backend;

class FacetTitles extends \Magento\Framework\App\Config\Value
{
    /**
     * JSON Encode value
     *
     * @return $this|void
     */
    public function beforeSave()
    {
        $this->setValue(json_encode(array_filter((array) $this->getValue())));

        return $this;
    }
}