<?php

namespace Clerk\Clerk\Block\Widget;

use Clerk\Clerk\Model\Config;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

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

    /**
     * Determine if we should show any output
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        if ($this->getType() === 'cart') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_CART_ENABLED)) {
                return;
            }
        }

        if ($this->getType() === 'category') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_CATEGORY_ENABLED)) {
                return;
            }
        }

        if ($this->getType() === 'product') {
            if (! $this->_scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_ENABLED)) {
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
        $output = '';
        $spanAttributes = [
            'class' => 'clerk',
            'data-template' => '@' . $this->getContent(),
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
            $spanAttributes['data-template'] = '@' . $this->getCartContent();
        }

        if ($this->getType() === 'category') {
            $spanAttributes['data-category'] = $this->getCurrentCategory();
            $spanAttributes['data-template'] = '@' . $this->getCategoryContent();
        }

        if ($this->getType() === 'product') {
            $spanAttributes['data-products'] = json_encode([$this->getCurrentProduct()]);
            $spanAttributes['data-template'] = '@' . $this->getProductContent();
        }

        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        return trim($output);
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
    protected function getCategoryContent()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_CATEGORY_CONTENT);
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
    protected function getProductContent()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_CONTENT);
    }

    /**
     * Get product ids from cart
     *
     * @return int[]
     */
    protected function getCartProducts()
    {
        return $this->cart->getProductIds();
    }

    /**
     * Get product ids from cart
     *
     * @return mixed
     */
    protected function getCartContent()
    {
        return $this->_scopeConfig->getValue(Config::XML_PATH_CART_CONTENT);
    }
}