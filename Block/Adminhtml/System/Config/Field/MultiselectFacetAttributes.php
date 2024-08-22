<?php

namespace Clerk\Clerk\Block\Adminhtml\System\Config\Field;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class MultiselectFacetAttributes extends Field
{

    /**
     * FacetTitles constructor.
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        ConfigHelper $configHelper,
        Context      $context,
        array        $data = []
    )
    {
        parent::__construct($context, $data);
        $this->configHelper = $configHelper;
    }

    /**
     * Get element html if facet attributes are configured
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if ($this->configHelper->getValueAdmin(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES)) {
            return parent::render($element);
        }
        return '';
    }
}
