<?php

namespace Clerk\Clerk\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class information extends \Magento\Config\Block\System\Config\Form\Field
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
     * @var ScopeConfigInterface
     */
    private $ScopeConfigInterface;

    /**
     * Version field constructor.
     *
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param ScopeConfigInterface $ScopeConfigInterface
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        ManagerInterface $messageManager,
        ScopeConfigInterface $ScopeConfigInterface,
        array $data = []
    ) {
        $this->moduleList = $moduleList;
        $this->messageManager = $messageManager;
        $this->ScopeConfigInterface = $ScopeConfigInterface;
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

        $currentUrl = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        $urlParts = explode("/", $currentUrl);

        $scope = 'default';

        if (in_array("store", $urlParts)) {
            $scope = 'store';
        }

        if (in_array("website", $urlParts)) {
            $scope = 'website';
        }

        $html = '';

        $singlestore =  $this->ScopeConfigInterface->getValue('general/single_store_mode/enabled');

        if ($singlestore !== '1' && $scope === 'default') {
            $html = 'Your current scope is "Default Config", to configure Clerk settings please change scope to "website" or "store.';
            return $this->_decorateRowHtml($element, $html);
        } else {

            return $this->_decorateRowHtml($element, $html);
        }
    }

    /**
     * Decorate field row html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html)
    {
        return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>';
    }
}
