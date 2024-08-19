<?php

namespace Clerk\Clerk\Block\Widget;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;

class Content extends Template implements BlockInterface
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
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param Registry $registry
     * @param Cart $cart
     * @param array $data
     */
    public function __construct(
        ConfigHelper     $configHelper,
        Template\Context $context,
        Registry         $registry,
        Cart             $cart,
        array            $data = []
    )
    {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->cart = $cart;
        $this->configHelper = $configHelper;
        $this->setTemplate('Clerk_Clerk::widget.phtml');
    }

    public function getEmbeds()
    {
        $contents = $this->getContent();
        if ($contents) {
            $contents = explode(',', $contents);
        }
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
        foreach ($contents as $content) {
            $output .= $this->getHtmlForContent(str_replace(' ', '', $content));
        }

        return $output;
    }

    /**
     * Get product ids from cart
     *
     * @return string[]
     */
    protected function getCartContents()
    {
        return $this->configHelper->getTemplates(Config::XML_PATH_CART_CONTENT);
    }

    /**
     * Get content for category page slider
     *
     * @return string[]
     */
    protected function getCategoryContents()
    {
        return $this->configHelper->getTemplates(Config::XML_PATH_CATEGORY_CONTENT);
    }

    /**
     * Get content for product page slider
     *
     * @return string[]
     */
    protected function getProductContents()
    {
        return $this->configHelper->getTemplates(Config::XML_PATH_PRODUCT_CONTENT);
    }

    private function getHtmlForContent($content)
    {
        $filter_category = $this->configHelper->getValue(Config::XML_PATH_CATEGORY_FILTER_DUPLICATES);
        $filter_product = $this->configHelper->getValue(Config::XML_PATH_PRODUCT_FILTER_DUPLICATES);
        $filter_cart = $this->configHelper->getValue(Config::XML_PATH_CART_FILTER_DUPLICATES);

        static $product_contents = 0;
        static $cart_contents = 0;
        static $category_contents = 0;

        $output = '<span ';
        $spanAttributes = [
            'class' => 'clerk',
            'data-template' => '@' . $content
        ];

        if ($this->getProductId()) {
            $value = explode('/', $this->getProductId());
            $productId = false;

            if (isset($value[0]) && isset($value[1]) && $value[0] == 'product') {
                $productId = $value[1];
            }

            if ($productId) {
                $spanAttributes['data-products'] = json_encode([$productId]);
                $spanAttributes['data-product'] = $productId;
            }
            if ($filter_product) {
                $spanAttributes = $this->getAttributes($product_contents, $spanAttributes);
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
            if ($filter_category) {
                $spanAttributes = $this->getAttributes($category_contents, $spanAttributes);
            }
        }

        if ($this->getType() === 'cart') {
            $spanAttributes['data-products'] = json_encode($this->getCartProducts());
            if ($filter_cart) {
                $spanAttributes = $this->getAttributes($cart_contents, $spanAttributes);
            }
        }

        if ($this->getType() === 'category') {
            $spanAttributes['data-category'] = $this->getCurrentCategory();
            if ($filter_category) {
                $spanAttributes = $this->getAttributes($category_contents, $spanAttributes);
            }
        }

        if ($this->getType() === 'product') {
            $spanAttributes['data-products'] = json_encode([$this->getCurrentProduct()]);
            $spanAttributes['data-product'] = $this->getCurrentProduct();
            if ($filter_product) {
                $spanAttributes = $this->getAttributes($product_contents, $spanAttributes);
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

    /**
     * @param int $product_contents
     * @param array $spanAttributes
     * @return array
     */
    public function getAttributes(int $product_contents, array $spanAttributes): array
    {
        $unique_class = "clerk_" . $product_contents;
        $spanAttributes['class'] = 'clerk ' . $unique_class;
        if ($product_contents > 0) {
            $filter_string = $this->getFilterClassString($product_contents);
            $spanAttributes['data-exclude-from'] = $filter_string;
        }
        return $spanAttributes;
    }

    /**
     * @param int $product_contents
     * @return string
     */
    public function getFilterClassString(int $product_contents): string
    {
        $filter_string = '';
        for ($i = 0; $i < $product_contents; $i++) {
            if ($i > 0) {
                $filter_string .= ', ';
            }
            $filter_string .= '.clerk_' . strval($i);
        }
        return $filter_string;
    }

    /**
     * @return array
     */
    protected function getCartProducts()
    {
        return array_values($this->cart->getProductIds());
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
        return null;
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
     * Determine if we should show any output
     *
     * @return string|void
     * @throws LocalizedException
     */
    protected function _toHtml()
    {
        if ($this->getType() === 'cart') {
            if (!$this->configHelper->getFlag(Config::XML_PATH_CART_ENABLED)) {
                return;
            }
        }
        if ($this->getType() === 'category') {
            if (!$this->configHelper->getFlag(Config::XML_PATH_CATEGORY_ENABLED)) {
                return;
            }
        }
        if ($this->getType() === 'product') {
            if (!$this->configHelper->getFlag(Config::XML_PATH_PRODUCT_ENABLED)) {
                return;
            }
        }
        return parent::_toHtml();
    }
}
