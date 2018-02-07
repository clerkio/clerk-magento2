<?php

namespace Clerk\Clerk\Block\Adminhtml\System\Config\Field;

use Clerk\Clerk\Model\Config;
use Magento\Config\Block\System\Config\Form\Field;

class MultiselectFacetAttributes extends Field
{
    /**
     * Get element html if facet attributes are configured
     * 
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES)) {
            return parent::render($element);
        }

        return '';
    }
}