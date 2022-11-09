<?php

namespace Clerk\Clerk\Block\Widget;

use Clerk\Clerk\Model\Config;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Store\Model\ScopeInterface;

class Content extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * Content constructor.
     * @param Template\Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        Cart $cart,
        array $data = []
        )
    {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->cart = $cart;
        $this->setTemplate('Clerk_Clerk::widget.phtml');
    }

    public function getEmbeds()
    {
        $contents = $this->getContent();

        if ($this->getType() === 'cart') {
            $contents = $this->getCartContents();
        }

        if ($this->getType() === 'category') {
            $contents = $this->getCategoryContents();
        }

        if ($this->getType() === 'product') {
            $contents = $this->getProductContents();
        }

        $output = '';

        if($contents){
            $contents_array = explode(',', $contents);
        } else {
            $contents_array = [0 => ''];
        }
        
        foreach ($contents_array as $content) {
            $output .= $this->getHtmlForContent(str_replace(' ','',$content));
        }

        return $output;
    }

    /**
     * Determine if we should show any output
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        if ($this->getType() === 'cart') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_CART_ENABLED, $scope, $scope_id)) {
                return;
            }
        }

        if ($this->getType() === 'category') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_CATEGORY_ENABLED, $scope, $scope_id)) {
                return;
            }
        }

        if ($this->getType() === 'product') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_ENABLED, $scope, $scope_id)) {
                return;
            }
        }

        return parent::_toHtml();
    }

    /**
     * Get attributes for Clerk span
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSpanAttributes()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        $filter_category = $this->_scopeConfig->getValue(Config::XML_PATH_CATEGORY_FILTER_DUPLICATES, $scope, $scope_id);
        $filter_product = $this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_FILTER_DUPLICATES, $scope, $scope_id);
        $filter_cart = $this->_scopeConfig->getValue(Config::XML_PATH_CART_FILTER_DUPLICATES, $scope, $scope_id);

        static $product_contents = 0;
        static $cart_contents = 0;
        static $category_contents = 0;

        $output = '<span ';
        $spanAttributes = [
            'class' => 'clerk',
            'data-template' => '@' . $this->getContents(),
        ];

        if ($this->getProductId()) {
            $value = explode('/', $this->getProductId());
            $productId = false;

            if (isset($value[0]) && isset($value[1]) && $value[0] == 'product') {
                $productId = $value[1];
            }

            if ($productId) {
                $spanAttributes['data-products'] = json_encode([$productId]);
            }
            if($filter_product){
                $unique_class = "clerk_" . (string)$product_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($product_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $product_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        if ($this->getCategoryId()) {
            $value = explode('/', $this->getCategoryId());
            $categoryId = false;

            if (isset($value[0]) && isset($value[1]) && $value[0] == 'category') {
                $categoryId = $value[1];
            }

            if ($categoryId) {
                $spanAttributes['data-category'] = $categoryId;
            }
            if($filter_category){
                $unique_class = "clerk_" . (string)$category_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($category_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $category_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        if ($this->getType() === 'cart') {
            $spanAttributes['data-products'] = json_encode($this->getCartProducts());
            $spanAttributes['data-template'] = '@' . $this->getCartContents();
            if($filter_cart){
                $unique_class = "clerk_" . (string)$cart_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($cart_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $cart_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        if ($this->getType() === 'category') {
            $spanAttributes['data-category'] = $this->getCurrentCategory();
            $spanAttributes['data-template'] = '@' . $this->getCategoryContents();
            if($filter_category){
                $unique_class = "clerk_" . (string)$category_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($category_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $category_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        if ($this->getType() === 'product') {
            $spanAttributes['data-products'] = json_encode([$this->getCurrentProduct()]);
            $spanAttributes['data-template'] = '@' . $this->getProductContents();
            if($filter_product){
                $unique_class = "clerk_" . (string)$product_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($product_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $product_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        $output .= '></span>';

        $product_contents++;
        $cart_contents++;
        $category_contents++;

        return $output;
    }

    /**
     * Get current category id
     *
     * @return mixed
     */
    protected function getCurrentCategory()
    {
        $category = $this->registry->registry('current_category');

        if ($category) {
            return $category->getId();
        }
    }

    /**
     * Get content for category page slider
     *
     * @return mixed
     */
    protected function getCategoryContents()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_CATEGORY_CONTENT, $scope, $scope_id);
    }

    /**
     * Get current product id
     *
     * @return mixed
     */
    protected function getCurrentProduct()
    {
        $product = $this->registry->registry('current_product');

        if ($product) {
            return $product->getId();
        }
    }

    /**
     * Get content for product page slider
     *
     * @return mixed
     */
    protected function getProductContents()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_CONTENT, $scope, $scope_id);
    }

    /**
     * @return array
     */
    protected function getCartProducts()
    {
        $products = array_values($this->cart->getProductIds());

        return $products;

    }

    /**
     * Get product ids from cart
     *
     * @return mixed
     */
    protected function getCartContents()
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        return $this->_scopeConfig->getValue(Config::XML_PATH_CART_CONTENT, $scope, $scope_id);
    }

    private function getHtmlForContent($content)
    {

        if($this->_scopeConfig->getValue('general/single_store_mode/enabled') == 1){
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        $filter_category = $this->_scopeConfig->getValue(Config::XML_PATH_CATEGORY_FILTER_DUPLICATES, $scope, $scope_id);
        $filter_product = $this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_FILTER_DUPLICATES, $scope, $scope_id);
        $filter_cart = $this->_scopeConfig->getValue(Config::XML_PATH_CART_FILTER_DUPLICATES, $scope, $scope_id);

        static $product_contents = 0;
        static $cart_contents = 0;
        static $category_contents = 0;

        $output = '<span ';
        $spanAttributes = [
            'class' => 'clerk',
            'data-template' => '@' . $content,
        ];

        if ($this->getProductId()) {
            $value = explode('/', $this->getProductId());
            $productId = false;

            if (isset($value[0]) && isset($value[1]) && $value[0] == 'product') {
                $productId = $value[1];
            }

            if ($productId) {
                $spanAttributes['data-products'] = json_encode([$productId]);
            }
            if($filter_product){
                $unique_class = "clerk_" . (string)$product_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($product_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $product_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        if ($this->getCategoryId()) {
            $value = explode('/', $this->getCategoryId());
            $categoryId = false;

            if (isset($value[0]) && isset($value[1]) && $value[0] == 'category') {
                $categoryId = $value[1];
            }

            if ($categoryId) {
                $spanAttributes['data-category'] = $categoryId;
            }
            if($filter_category){
                $unique_class = "clerk_" . (string)$category_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($category_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $category_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        if ($this->getType() === 'cart') {
            $spanAttributes['data-products'] = json_encode($this->getCartProducts());
            if($filter_cart){
                $unique_class = "clerk_" . (string)$cart_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($cart_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $cart_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        if ($this->getType() === 'category') {
            $spanAttributes['data-category'] = $this->getCurrentCategory();
            if($filter_category){
                $unique_class = "clerk_" . (string)$category_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($category_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $category_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        if ($this->getType() === 'product') {
            $spanAttributes['data-products'] = json_encode([$this->getCurrentProduct()]);
            if($filter_product){
                $unique_class = "clerk_" . (string)$product_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($product_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $product_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        $output .= "></span>\n";

        $product_contents++;
        $cart_contents++;
        $category_contents++;

        return $output;
    }
}
