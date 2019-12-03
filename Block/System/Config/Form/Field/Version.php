<?php

namespace Clerk\Clerk\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\ModuleListInterface;

class Version extends \Magento\Config\Block\System\Config\Form\Field
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
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        $this->moduleList = $moduleList;
        $this->messageManager = $messageManager;

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
        $modules = $this->moduleList->getAll();

        $modules_for_warning = [
            //'Clerk_Clerk' => ['message' => 'This module can interfear with how we inject our instant search.', 'link' => 'https://clerk.io']
        ];

        foreach ($modules as $name => $module) {

            if (array_key_exists($name, $modules_for_warning)) {

                $this->messageManager->addWarning(__('<strong style="color:#eb5e00">Warning: </strong>'.$name.' is installed. '.$modules_for_warning[$name]['message'].'.<a target="_blank" href="'.$modules_for_warning[$name]['link'].'"> Read more here</a>'));

            }
        }

        //Get installed module version
        $moduleInfo = $this->moduleList->getOne('Clerk_Clerk');
        $installedVersion = $moduleInfo['setup_version'];

        return $installedVersion;
    }
}