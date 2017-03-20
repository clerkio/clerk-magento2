<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;

class Powerstep extends AbstractProduct
{
//    /**
//     * Powerstep constructor.
//     *
//     * @param Context $context
//     * @param array $data
//     */
//    public function __construct(Context $context, CartHelper $cartHelper, array $data = [])
//    {
//        $this->cartHelper = $cartHelper;
//
//        parent::__construct($context, $data);
//    }

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

    public function getTemplates()
    {
        $configTemplates = $this->_scopeConfig->getValue(Config::XML_PATH_POWERSTEP_TEMPLATES);
        $templates = explode(',', $configTemplates);

        return (array) $templates;
    }
}