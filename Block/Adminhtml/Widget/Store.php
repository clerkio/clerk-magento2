<?php

namespace Clerk\Clerk\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Select as FormSelect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Option\ArrayPool;

class Store extends Widget
{

    /**
     * @var FormSelect
     */
    protected $_formSelect;

    /**
     * @var ArrayPool
     */
    protected $_sourceModelPool;

    /**
     * Store constructor.
     * @param Context $context
     * @param ArrayPool $sourceModelPool
     * @param FormSelect $formSelect
     * @param array $data
     */
    public function __construct(
        Context    $context,
        ArrayPool  $sourceModelPool,
        FormSelect $formSelect,
        array      $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_sourceModelPool = $sourceModelPool;
        $this->_formSelect = $formSelect;
    }

    /**
     * Prepare chooser element HTML
     *
     * @param AbstractElement $element Form Element
     * @return AbstractElement
     * @throws LocalizedException
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        $uniqId = $this->mathRandom->getUniqueHash($element->getId());

        $ajaxUrl = $this->getUrl(
            'clerk/widget/'
        );

        //Since we're using block as widget parameter type we need to create the select ourselves
        $select = $this->_formSelect;
        $select->setHtmlId($element->getHtmlId());
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $select->setName($element->getName());
        $configuredValue = $element->getData();
        if ($configuredValue !== null) {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $select->setValue($element->getData());
        }
        $select->setValues($this->_sourceModelPool->get('Magento\Config\Model\Config\Source\Store')->toOptionArray());
        $select->setForm($element->getForm());

        echo get_class($element->getForm());

        //Create javascript block and append
        /** @var Template $jsBlock */
        $jsBlock = $this->getLayout()->createBlock('Magento\Backend\Block\Template')
            ->setTemplate('Clerk_Clerk::widget.phtml')
            ->setAjaxUrl($ajaxUrl)
            ->setSelectId($element->getHtmlId());

        $element->setData('after_element_html', $select->toHtml() . $jsBlock->toHtml());

        return $element;
    }
}
