<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\ScopeInterface;

class Powerstep extends AbstractProduct
{

    protected $storeManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
        )
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Get Cart URL
     *
     * @return string
     */
    public function getCartUrl()
    {
        return $this->_cartHelper->getCartUrl();
    }

    /**
     * Get Checkout URL
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('checkout', ['_secure' => true]);
    }

    /**
     * Get product added
     *
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->hasData('current_product')) {
            $this->setData('current_product', $this->_coreRegistry->registry('current_product'));
        }

        return $this->getData('current_product');
    }

    /**
     * Get image url for product
     *
     * @return string
     */
    public function getImageUrl()
    {
        $product = $this->getProduct();
        return $this->_imageHelper->init($product, 'product_page_image_small')
            ->setImageFile($product->getImage())
            ->getUrl();
    }

    public function getExcludeState()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_POWERSTEP_FILTER_DUPLICATES, $scope, $scope_id);
    }

    public function getTemplates()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->storeManager->getStore()->getId();
        }

        $configTemplates = $this->_scopeConfig->getValue(Config::XML_PATH_POWERSTEP_TEMPLATES, $scope, $scope_id);
        $templates = explode(',', $configTemplates);

        foreach ($templates as $key => $template) {

            $templates[$key] = str_replace(' ','',$template);

        }

        return (array) $templates;
    }

    public function generateRandomString($length = 25) {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;

    }
}
