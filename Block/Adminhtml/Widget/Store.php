<?php

namespace Clerk\Clerk\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Option\ArrayPool;
use Magento\Framework\Data\Form\Element\Select as FormSelect;

class Store extends \Magento\Backend\Block\Widget
{

    /**
     * @var FormSelect
     */
    protected $_formSelect;

    /**
     * @var \Magento\Framework\Option\ArrayPool
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
        Context $context,
        ArrayPool $sourceModelPool,
        FormSelect $formSelect,
        array $data = []
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        $uniqId = $this->mathRandom->getUniqueHash($element->getId());

        $ajaxUrl = $this->getUrl(
            'clerk/widget/'
        );

        //Since we're using block as widget parameter type we need to create the select ourselves
        /** @var \Magento\Framework\Data\Form\Element\Select $select */
        $select = $this->_formSelect;
        $select->setHtmlId($element->getHtmlId());
        $select->setName($element->getName());
        $configuredValue = $element->getData();
        if ($configuredValue !== null) {
            $select->setValue($element->getData());
        }
        $select->setValues($this->_sourceModelPool->get('Magento\Config\Model\Config\Source\Store')->toOptionArray());
        $select->setForm($element->getForm());

        echo get_class($element->getForm());

        //Create javascript block and append
        /** @var \Magento\Backend\Block\Template $jsBlock */
        $jsBlock = $this->getLayout()->createBlock('Magento\Backend\Block\Template')
            ->setTemplate('Clerk_Clerk::widget.phtml')
            ->setAjaxUrl($ajaxUrl)
            ->setSelectId($element->getHtmlId());

        $element->setData('after_element_html', $select->toHtml() . $jsBlock->toHtml());

        return $element;
    }
}
