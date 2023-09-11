<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magento\Directory\Model\Currency;
use Magento\Store\Model\StoreManagerInterface;

class Tracking extends Template
{

    protected $formKey;

    protected $_currency;

    protected $_storeManager;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        Currency $_currency,
        StoreManagerInterface $_storeManager
    ) {
        parent::__construct($context);
        $this->formKey = $formKey;
        $this->_currency = $_currency;
        $this->_storeManager = $_storeManager;

    }
    /**
     * Get public key
     *
     * @return mixed
     */
    public function getPublicKey()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(
            Config::XML_PATH_PUBLIC_KEY,
            $scope,
            $scope_id
        );
    }

    public function getLanguage()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(
            Config::XML_PATH_LANGUAGE,
            $scope,
            $scope_id
        );
    }

    /**
     * Get collect emails
     *
     * @return string
     */
    public function getCollectionEmails()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return ($this->_scopeConfig->isSetFlag(
            Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS,
            $scope,
            $scope_id
        ) ? 'true' : 'false');
    }

    /**
     * Get collect carts
     *
     * @return string
     */
    public function getCollectionBaskets()
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        $collectBaskets = "false";
        if ($this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS, $scope, $scope_id) == '1') {
            $collectBaskets = "true";
        }
        return $collectBaskets;
    }

    public function getFormKey()
    {

        return $this->formKey->getFormKey();
    }

    /**
     * Get store base currency code
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * Get current store currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get default store currency code
     *
     * @return string
     */
    public function getDefaultCurrencyCode()
    {
        return $this->_storeManager->getStore()->getDefaultCurrencyCode();
    }

    /**
     * Get allowed store currency codes
     *
     * If base currency is not allowed in current website config scope,
     * then it can be disabled with $skipBaseNotAllowed
     *
     * @param bool $skipBaseNotAllowed
     * @return array
     */
    public function getAvailableCurrencyCodes($skipBaseNotAllowed = false)
    {
        return $this->_storeManager->getStore()->getAvailableCurrencyCodes($skipBaseNotAllowed);
    }

    /**
     * Get array of installed currencies for the scope
     *
     * @return array
     */
    public function getAllowedCurrencies()
    {
        return $this->_storeManager->getStore()->getAllowedCurrencies();
    }

    /**
     * Get current currency rate
     *
     * @return float
     */
    public function getCurrentCurrencyRate()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyRate();
    }

    /**
     * Get currency symbol for current locale and currency code
     *
     * @return string
     */
    public function getCurrentCurrencySymbol()
    {
        return $this->_currency->getCurrencySymbol();
    }

    /**
     * Get currency rate for current locale from currency code
     *
     * @return float
     */

    public function getCurrencyRateFromIso($currencyIso = null) {
        if( ! $currencyIso ) {
            return 1.0;
        } else {
            return $this->_storeManager->getStore()->getBaseCurrency()->getRate($currencyIso);
        }
    }

    public function getAllCurrencyRates() {
        $currency_codes = $this->getAllowedCurrencies();
        $currency_rates_array = array();
        foreach($currency_codes as $key => $code){
            $currency_rates_array[$code] = $this->getCurrencyRateFromIso($code);
        }
        return $currency_rates_array;
    }

}