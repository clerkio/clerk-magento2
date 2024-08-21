<?php

namespace Clerk\Clerk\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\ModuleListInterface;

class Version extends Field
{
    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;


    /**
     * Version field constructor.
     *
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Context             $context,
        ModuleListInterface $moduleList,
        ManagerInterface    $messageManager,
        array               $data = []
    ) {
        $this->moduleList = $moduleList;
        $this->messageManager = $messageManager;

        parent::__construct($context, $data);
    }

    /**
     * Render form field
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
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
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $moduleInfo = $this->moduleList->getOne('Clerk_Clerk');
        return $moduleInfo['setup_version'];
    }
}
