<?php

namespace Clerk\Clerk\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\ModuleListInterface;

class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * Version field constructor.
     *
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        $this->moduleList = $moduleList;

        parent::__construct($context, $data);
    }

    /**
     * Render form field
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //Hide scope label and inheritance checkbox
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        $element->setCanRestoreToDefault(false);
        $element->setScope(false);

        return parent::render($element);
    }

    /**
     * Get installed version
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //Get installed module version
        $moduleInfo = $this->moduleList->getOne('Clerk_Clerk');
        $installedVersion = $moduleInfo['setup_version'];

        return $installedVersion;
    }
}