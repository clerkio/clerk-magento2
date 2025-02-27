<?php
/**
 * Tracking Block for Clerk.io
 */

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;

class Tracking extends Template
{

    protected $formKey;

    protected $_currency;

    protected $_storeManager;

    protected $_customerSession;

    protected $_localeCurrency;

    protected $urlEncoder;

    protected $urlBuilder;

    public function __construct(
        Context               $context,
        FormKey               $formKey,
        StoreManagerInterface $_storeManager,
        Session               $_customerSession,
        CurrencyInterface     $_localeCurrency,
        Template\Context      $context,
        EncoderInterface      $urlEncoder,
        UrlInterface          $urlBuilder,
        array $data = []
    )
    {
        parent::__construct($context);
        $this->formKey = $formKey;
        $this->_storeManager = $_storeManager;
        $this->_customerSession = $_customerSession;
        $this->_localeCurrency = $_localeCurrency;
        $this->urlEncoder = $urlEncoder;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }


    /**
     * @return string
     */
    public function getCustomerEmail()
    {
        $email = "";
        try {
            $customerData = $this->_customerSession->getCustomer();
            $email = $customerData->getEmail();
        } catch (\Exception $e) {
        }
        return $email;
    }

    /**
     * Get public key
     *
     * @return mixed
     * @throws NoSuchEntityException
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
     * @throws NoSuchEntityException
     */
    public function getCollectionEmails($as_bool = false)
    {

        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        if ($as_bool) {
            return $this->_scopeConfig->isSetFlag(
                Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS,
                $scope,
                $scope_id
            );
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
     * @throws NoSuchEntityException
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

    public function getEncodedUrl()
    {
      $currentUrl = $this->urlBuilder->getCurrentUrl();
      return $this->urlEncoder->encode($currentUrl);
    }

    public function getFormKey()
    {
        if (array_key_exists('form_key', $_COOKIE)) {
            return $_COOKIE['form_key'];
        }
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
     * @param bool $skipBaseNotAllowed Skip base currencies
     *
     * @return array
     */
    public function getAvailableCurrencyCodes($skipBaseNotAllowed = false)
    {
        return $this->_storeManager->getStore()->getAvailableCurrencyCodes($skipBaseNotAllowed);
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
        return $this->_localeCurrency->getCurrency($this->getCurrentCurrencyCode())->getSymbol();
    }

    /**
     * Get all currency symbols as a map
     *
     * @return array
     */
    public function getAllCurrencySymbols()
    {
        $currency_codes = $this->getAllowedCurrencies();
        $currency_symbols_array = array();
        foreach ($currency_codes as $code) {
            $currency_symbols_array[$code] = $this->_localeCurrency->getCurrency($code)->getSymbol();
        }
        return $currency_symbols_array;
    }

    /**
     * Get all currency rates as a map
     *
     * @return array
     */
    public function getAllCurrencyRates()
    {
        $currency_codes = $this->getAllowedCurrencies();
        $currency_rates_array = array();
        foreach ($currency_codes as $code) {
            $currency_rates_array[$code] = $this->getCurrencyRateFromIso($code);
        }
        return $currency_rates_array;
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
     * Get currency rate for current locale from currency code
     *
     * @param string|null $currencyIso Currency ISO code
     *
     * @return float
     */
    public function getCurrencyRateFromIso($currencyIso = null)
    {
        if (!$currencyIso) {
            return 1.0;
        } else {
            return $this->_storeManager->getStore()->getBaseCurrency()->getRate($currencyIso);
        }
    }

    public function getClerkJSLink()
    {
        $storeName = $this->getStoreNameSlug() ?? 'clerk';
        return '://custom.clerk.io/' . $storeName . '.js';
    }

    public function getStoreNameSlug()
    {
        $storeName = $this->_storeManager->getStore()->getName();
        $storeName = preg_replace('/[^a-z]/', '', strtolower($storeName));
        return $storeName;
    }
}
