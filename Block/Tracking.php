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

    protected $formKey;

    protected $_currency;

    protected $_storeManager;

    protected $_customerSession;

    public function __construct(
        ConfigHelper          $configHelper,
        Context               $context,
        FormKey               $formKey,
        Currency              $_currency,
        StoreManagerInterface $_storeManager,
        Session               $_customerSession
    )
    {
        parent::__construct($context);
        $this->formKey = $formKey;
        $this->_currency = $_currency;
        $this->_storeManager = $_storeManager;
        $this->_customerSession = $_customerSession;
        $this->configHelper = $configHelper;
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
        } catch (Exception $ex) {
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

    public function getLanguage()
    {
        return $this->configHelper->getValue(Config::XML_PATH_LANGUAGE);
    }

    /**
     * Get collect emails
     *
     * @return string
     */
    public function getCollectionEmails($as_bool = false)
    {
        if ($as_bool) {
            return $this->configHelper->getFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS);
        }
        return ($this->configHelper->getFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS) ? 'true' : 'false');
    }

    /**
     * Get collect carts
     *
     * @return string
     */
    public function getCollectionBaskets()
    {
        return ($this->configHelper->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS)) ? "true" : "false";
    }

    /**
     * @return mixed|string
     * @throws LocalizedException
     */
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
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAllCurrencyRates()
    {
        $currency_codes = $this->getAllowedCurrencies();
        $currency_rates_array = array();
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
     * @return string
     * @throws NoSuchEntityException
     */
    public function getClerkJSLink()
    {
        $storeName = $this->getStoreNameSlug() ?? 'clerk';
        return '://custom.clerk.io/' . $storeName . '.js';
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreNameSlug()
    {
        $storeName = $this->_storeManager->getStore()->getName();
        return preg_replace('/[^a-z]/', '', strtolower($storeName));
    }
}
