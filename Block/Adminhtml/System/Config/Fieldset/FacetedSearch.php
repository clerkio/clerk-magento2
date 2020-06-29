<?php

namespace Clerk\Clerk\Block\Adminhtml\System\Config\Fieldset;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Magento\Config\Model\ResourceModel\Config as SystemConfig;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;

class FacetedSearch extends Fieldset
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * FacetedSearch constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param Api $api
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        Api $api,
        SystemConfig $systemConfig,
        array $data = [])
    {
        $this->api = $api;
        $this->systemConfig = $systemConfig;

        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Render fieldset
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        $header = $this->_getHeaderHtml($element);

        if (! $this->isConfigured()) {
            $elements = __('Public and private key must be set in order to enable faceted search');
        } else {
            if (! $this->keysValid()) {
                $elements = __('Public or private key invalid');
            } else {
                $elements = $this->_getChildrenElementsHtml($element);
            }
        }


        $footer = $this->_getFooterHtml($element);

        return $header . $elements . $footer;
    }

    /**
     * Determine if private & public key is set
     *
     * @return bool
     */
    private function isConfigured()
    {
        return (bool) ($this->_scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE) && $this->_scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY, ScopeInterface::SCOPE_STORE));
    }

    /**
     * Determine if public & private keys are valid
     *
     * @return bool
     */
    private function keysValid()
    {
        $publicKey = $this->_scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_STORE);
        $privateKey = $this->_scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY, ScopeInterface::SCOPE_STORE);

        $keysValid = json_decode($this->api->keysValid($publicKey, $privateKey));

        if ($keysValid->status === 'error') {
            return false;
        }

        return true;
    }
}
