<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use function GuzzleHttp\Psr7\str;

class PowerstepPopup extends Template
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Cart
     */
    protected $cartHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * PowerstepPopup constructor.
     *
     * @param Template\Context $context
     * @param array $data
     * @param Session $checkoutSession
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        ProductRepositoryInterface $productRepository,
        Cart $cartHelper,
        Image $imageHelper,
        array $data = [])
    {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
        $this->cartHelper = $cartHelper;
        $this->imageHelper = $imageHelper;

        $this->setTemplate('powerstep_popup.phtml');
    }

    /**
     * Get product added
     *
     * @return Product
     */
    public function getProduct()
    {
        $productId = $this->checkoutSession->getClerkProductId();

        try {
            return $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Get header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($product = $this->getProduct()) {
            return __(
                'You added %1 to your shopping cart.',
                $product->getName()
            );
        }

        return "failed to load product with id" . $this->checkoutSession->getClerkProductId();
    }

    /**
     * Get Cart URL
     *
     * @return string
     */
    public function getCartUrl()
    {
        return $this->cartHelper->getCartUrl();
    }

    /**
     * Get image url for product
     *
     * @return string
     */
    public function getImageUrl()
    {
        $product = $this->getProduct();

        return $this->imageHelper->init($product, 'product_page_image_small')
            ->setImageFile($product->getImage())
            ->getUrl();
    }

    /**
     * Determine if we should show popup block
     *
     * @return mixed
     */
    public function shouldShow()
    {
        return ($this->getRequest()->getParam('isAjax')) || ($this->checkoutSession->getClerkShowPowerstep(true));
    }

    /**
     * Determine if request is ajax
     *
     * @return mixed
     */
    public function isAjax()
    {
        return $this->getRequest()->getParam('isAjax');
    }

    /**
     * Get powerstep templates
     *
     * @return array
     */
    public function getTemplates()
    {
        $configTemplates = $this->_scopeConfig->getValue(Config::XML_PATH_POWERSTEP_TEMPLATES, ScopeInterface::SCOPE_STORE);
        $templates = explode(',', $configTemplates);

        foreach ($templates as $key => $template) {

            $templates[$key] = str_replace(' ','', $template);

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
