<?php

namespace Clerk\Clerk\Block\Adminhtml\System\Config\Field;

use Clerk\Clerk\Model\Config;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Store\Model\ScopeInterface;

class MultiselectFacetAttributes extends Field
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
        array $data = [])
    {
        $this->requestInterface = $requestInterface;
        parent::__construct($context, $data);
    }

    /**
     * Get element html if facet attributes are configured
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
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
        if ($this->_scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, $scope, $scope_id)) {
            return parent::render($element);
        }

        return '';
    }
}
