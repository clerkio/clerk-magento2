<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Checkout\Helper\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

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
     * @param Context $context
     * @param Session $checkoutSession
     * @param ProductRepositoryInterface $productRepository
     * @param Cart $cartHelper
     * @param Image $imageHelper
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Template\Context           $context,
        Session                    $checkoutSession,
        ProductRepositoryInterface $productRepository,
        Cart                       $cartHelper,
        Image                      $imageHelper,
        ConfigHelper               $configHelper,
        array                      $data = []
    )
    {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
        $this->cartHelper = $cartHelper;
        $this->imageHelper = $imageHelper;
        $this->configHelper = $configHelper;
        $this->setTemplate('powerstep_popup.phtml');
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

        return "failed to load product with id" . $this->getLastProductId();
    }

    /**
     * Get product added
     *
     * @return false|ProductInterface
     */
    public function getProduct()
    {
        $product_id = $this->getLastProductId();
        if ($product_id === false) {
            return false;
        }
        try {
            return $this->productRepository->getById($product_id);
        } catch (NoSuchEntityException) {
            return false;
        }
    }

    /**
     * @return false|int
     */
    public function getLastProductId()
    {
        try {
            $product_id = $this->checkoutSession->getClerkProductId();
            if (!empty($product_id)) {
                return $product_id;
            }
            $items_collection = $this->checkoutSession->getQuote()->getItemsCollection();
            $items_collection->getSelect()->order('created_at DESC');
            $last_item = $items_collection->getLastItem();
            if (empty($last_item)) {
                return false;
            }
            return $last_item->getProductId();
        } catch (NoSuchEntityException|LocalizedException) {
            return false;
        }
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
        if ($product = $this->getProduct()) {
            return $this->imageHelper->init($product, 'product_page_image_small')
                ->setImageFile($product->getImage())
                ->getUrl();
        }

        return '';
    }

    /**
     * Determine if we should show popup block
     *
     * @return bool
     */
    public function shouldShow()
    {
        $show_powerstep = ($this->getRequest()->getParam('isAjax')) || ($this->checkoutSession->getClerkShowPowerstep(true));

        if ($show_powerstep) {
            $this->checkoutSession->setClerkShowPowerstep(false);
        }

        return $show_powerstep;
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

    public function getExcludeState()
    {
        return $this->configHelper->getValue(Config::XML_PATH_POWERSTEP_FILTER_DUPLICATES);
    }

    /**
     * Get powerstep templates
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->configHelper->getTemplates(Config::XML_PATH_POWERSTEP_TEMPLATES);
    }
}
