<?php

namespace Clerk\Clerk\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Option\ArrayPool;

class Store extends \Magento\Backend\Block\Widget
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Option\ArrayPool
     */
    protected $_sourceModelPool;

    /**
     * Store constructor.
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(Context $context, ObjectManagerInterface $objectManager, ArrayPool $sourceModelPool, array $data = [])
    {
        parent::__construct($context, $data);
        $this->_objectManager = $objectManager;
        $this->_sourceModelPool = $sourceModelPool;
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
        $select = $this->_objectManager->create('Magento\Framework\Data\Form\Element\Select');
        $select->setHtmlId($element->getHtmlId());
        $select->setName($element->getName());
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