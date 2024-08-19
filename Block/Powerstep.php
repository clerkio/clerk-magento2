<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;

// TODO: FIX DEPRECATIONS

class Powerstep extends AbstractProduct
{

    public function __construct(
        ConfigHelper $configHelper,
        Context      $context,
        array        $data = []
    )
    {
        parent::__construct($context, $data);
        $this->configHelper = $configHelper;
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
     * @return mixed
     */
    public function getExcludeState()
    {
        return $this->configHelper->getValue(Config::XML_PATH_POWERSTEP_FILTER_DUPLICATES);
    }

    /**
     * @return array|string[]
     */
    public function getTemplates()
    {
        return $this->configHelper->getTemplates(Config::XML_PATH_POWERSTEP_TEMPLATES);
    }

}
