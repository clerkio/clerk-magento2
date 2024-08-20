<?php

namespace Clerk\Clerk\Block\Adminhtml\System\Config\Field;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;

class FacetTitles extends Field
{

    /**
     * FacetTitles constructor.
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        ConfigHelper     $configHelper,
        Context $context,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->setTemplate('Clerk_Clerk::facettitles.phtml');
        parent::__construct($context, $data);
    }

    /**
     * Get configured facet attributes
     */
    public function getConfiguredAttributes()
    {
        $attributes = $this->configHelper->getValueAdmin(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES);
        return is_string($attributes) ? explode(',', $attributes) : [];
    }

    /**
     * Get label for current scope
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getScopeLabel()
    {
        return $this->_storeManager->getStore($this->getStoreId())->getName();
    }

    /**
     * Get store id
     *
     * @return mixed
     */
    public function getStoreId()
    {
        if (!$this->hasData('store_id')) {
            $this->setData('store_id', (int)$this->getRequest()->getParam('store'));
        }

        return $this->getData('store_id');
    }

    /**
     * Get html for element
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);

        return $this->toHtml();
    }
}
