<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Tracking extends Template
{

    protected $formKey;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey
    ) {
        parent::__construct($context);
        $this->formKey = $formKey;
    }
    /**
     * Get public key
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->_scopeConfig->getValue(
            Config::XML_PATH_PUBLIC_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getLanguage()
    {
        return $this->_scopeConfig->getValue(
            Config::XML_PATH_LANGUAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get collect emails
     *
     * @return string
     */
    public function getCollectionEmails()
    {
        return ($this->_scopeConfig->isSetFlag(
            Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS,
            ScopeInterface::SCOPE_STORE
        ) ? 'true' : 'false');
    }

    public function getFormKey() {

        return $this->formKey->getFormKey();

    }
}
