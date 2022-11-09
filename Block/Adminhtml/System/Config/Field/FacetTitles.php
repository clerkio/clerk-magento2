<?php

namespace Clerk\Clerk\Block\Adminhtml\System\Config\Field;

use Clerk\Clerk\Model\Config;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Store\Model\ScopeInterface;

class FacetTitles extends Field
{
    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * FacetTitles constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\RequestInterface $requestInterface,
        array $data = []
        )
    {
        $this->requestInterface = $requestInterface;
        $this->setTemplate('Clerk_Clerk::facettitles.phtml');
        parent::__construct($context, $data);
    }

    /**
     * Get html for element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);

        $html = '';

        $html .= $this->toHtml();

        return $html;
    }

    /**
     * Get configured facet attributes
     */
    public function getConfiguredAttributes()
    {
        $_params = $this->requestInterface->getParams();
        $scope_id = '0';
        $scope = 'default';
        if (array_key_exists('website', $_params)){
            $scope = 'website';
            $scope_id = $_params[$scope];
        }
        if (array_key_exists('store', $_params)){
            $scope = 'store';
            $scope_id = $_params[$scope];
        }
        $attributes = $this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, $scope, $scope_id);
        $configuredAttributes = is_string($attributes) ? explode(',', $attributes) : array();

        return $configuredAttributes;
    }

    /**
     * Get label for current scope
     *
     * @return string
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
}
