<?php
/**
 * Tracking Block for Clerk.io
 */

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

class Tracking extends Template
{

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var Currency
     */
    protected $_currency;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param FormKey $formKey
     * @param Currency $_currency
     * @param StoreManagerInterface $_storeManager
     * @param Session $_customerSession
     */
    public function __construct(
        ConfigHelper          $configHelper,
        Context               $context,
        FormKey               $formKey,
        Currency              $_currency,
        StoreManagerInterface $_storeManager,
        Session               $_customerSession
    ) {
        parent::__construct($context);
        $this->formKey = $formKey;
        $this->_currency = $_currency;
        $this->_storeManager = $_storeManager;
        $this->_customerSession = $_customerSession;
        $this->configHelper = $configHelper;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        $email = "";
        try {
            $customerData = $this->_customerSession->getCustomer();
            $email = $customerData->getEmail();
        } catch (Exception $ex) {
            return $email;
        }
        return $email;
    }

    /**
     * Get public key
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->configHelper->getValue(Config::XML_PATH_PUBLIC_KEY);
    }

    /**
     * Get the scope language string
     *
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LANGUAGE);
    }

    /**
     * Get collect emails
     *
     * @param bool $as_bool
     * @return string
     */
    public function getCollectionEmails($as_bool = false)
    {
        if ($as_bool) {
            return $this->configHelper->getFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS);
        }
        return $this->configHelper->getFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS) ? 'true' : 'false';
    }

    /**
     * Get collect carts
     *
     * @return string
     */
    public function getCollectionBaskets()
    {
        return $this->configHelper->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS) ? "true" : "false";
    }

    /**
     * Get context FormKey
     *
     * @return string
     * @throws LocalizedException
     */
    public function getFormKey()
    {
        return !empty($_COOKIE) && array_key_exists('form_key', $_COOKIE) ? $_COOKIE['form_key'] : $this->formKey->getFormKey();
    }

    /**
     * Get store base currency code
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * Get current store currency code
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get default store currency code
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultCurrencyCode()
    {
        return $this->_storeManager->getStore()->getDefaultCurrencyCode();
    }

    /**
     * Get allowed store currency codes
     *
     * If the base currency is not allowed in current website config scope,
     * then it can be disabled with $skipBaseNotAllowed
     *
     * @param bool $skip_base_not_allowed
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAvailableCurrencyCodes($skip_base_not_allowed = false)
    {
        return $this->_storeManager->getStore()->getAvailableCurrencyCodes($skip_base_not_allowed);
    }

    /**
     * Get current currency rate
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
     * Get all enabled currency rates
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAllCurrencyRates()
    {
        $currency_codes = $this->getAllowedCurrencies();
        $currency_rates_array = [];
        foreach ($currency_codes as $key => $code) {
            $currency_rates_array[$code] = $this->getCurrencyRateFromIso($code);
        }
        return $currency_rates_array;
    }

    /**
     * Get an array of installed currencies for the scope
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAllowedCurrencies()
    {
        return $this->_storeManager->getStore()->getAllowedCurrencies();
    }

    /**
     * Get currency rate for current locale from currency code
     *
     * @param string|null $currency_iso Currency ISO code
     *
     * @return float
     * @throws NoSuchEntityException
     */
    public function getCurrencyRateFromIso($currency_iso = null)
    {
        if (!$currency_iso) {
            return 1.0;
        } else {
            return $this->_storeManager->getStore()->getBaseCurrency()->getRate($currency_iso);
        }
    }

    /**
     * Get clerk.js custom URL
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getClerkJSLink()
    {
        $storeName = $this->getStoreNameSlug() ?? 'clerk';
        return '://custom.clerk.io/' . $storeName . '.js';
    }

    /**
     * Get handleized version of the store name
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreNameSlug()
    {
        $storeName = $this->_storeManager->getStore()->getName();
        return preg_replace('/[^a-z]/', '', strtolower($storeName));
    }
}
