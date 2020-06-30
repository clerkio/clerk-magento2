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
    public function __construct(Template\Context $context, Registry $registry, Cart $cart, array $data = [])
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

        foreach (explode(',', $contents) as $content) {
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
        if ($this->getType() === 'cart') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_CART_ENABLED, ScopeInterface::SCOPE_STORE)) {
                return;
            }
        }

        if ($this->getType() === 'category') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_CATEGORY_ENABLED, ScopeInterface::SCOPE_STORE)) {
                return;
            }
        }

        if ($this->getType() === 'product') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_ENABLED, ScopeInterface::SCOPE_STORE)) {
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
        }

        if ($this->getType() === 'cart') {
            $spanAttributes['data-products'] = json_encode($this->getCartProducts());
            $spanAttributes['data-template'] = '@' . $this->getCartContents();
        }

        if ($this->getType() === 'category') {
            $spanAttributes['data-category'] = $this->getCurrentCategory();
            $spanAttributes['data-template'] = '@' . $this->getCategoryContents();
        }

        if ($this->getType() === 'product') {
            $spanAttributes['data-products'] = json_encode([$this->getCurrentProduct()]);
            $spanAttributes['data-template'] = '@' . $this->getProductContents();
        }

        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        $output .= '></span>';

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
        return $this->_scopeConfig->getValue(Config::XML_PATH_CATEGORY_CONTENT, ScopeInterface::SCOPE_STORE);
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
        return $this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_CONTENT, ScopeInterface::SCOPE_STORE);
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
        return $this->_scopeConfig->getValue(Config::XML_PATH_CART_CONTENT, ScopeInterface::SCOPE_STORE);
    }

    private function getHtmlForContent($content)
    {
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
        }

        if ($this->getType() === 'cart') {
            $spanAttributes['data-products'] = json_encode($this->getCartProducts());
        }

        if ($this->getType() === 'category') {
            $spanAttributes['data-category'] = $this->getCurrentCategory();
        }

        if ($this->getType() === 'product') {
            $spanAttributes['data-products'] = json_encode([$this->getCurrentProduct()]);
        }

        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        $output .= "></span>\n";

        return $output;
    }
}
