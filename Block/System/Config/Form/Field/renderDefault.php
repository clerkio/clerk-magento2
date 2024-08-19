<?php

namespace Clerk\Clerk\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

class renderDefault extends Field
{
    /**
     * @var ScopeConfigInterface
     */
    private $ScopeConfigInterface;

    /**
     * renderDefault field constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $ScopeConfigInterface
     * @param array $data
     */
    public function __construct(
        Context              $context,
        ScopeConfigInterface $ScopeConfigInterface,
        array                $data = []
    )
    {
        $this->ScopeConfigInterface = $ScopeConfigInterface;
        parent::__construct($context, $data);
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
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

        $singlestore = $this->ScopeConfigInterface->getValue('general/single_store_mode/enabled');

        if ($singlestore === '1' && $scope === 'default') {
            return parent::render($element, $this->ScopeConfigInterface);
        } elseif ($singlestore !== '1' && $scope !== 'default') {
            return parent::render($element, $this->ScopeConfigInterface);
        } else {
            return $this->_decorateRowHtml($element, $html);
        }
    }

    /**
     * Decorate field row html
     *
     * @param AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml(AbstractElement $element, $html)
    {
        return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>';
    }
}
