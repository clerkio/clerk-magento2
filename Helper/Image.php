<?php

namespace Clerk\Clerk\Helper;

use Clerk\Clerk\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Image
{
    /**
     * @var RequestInterface
     */
    protected $requestInterface;
    /**
     * @var ImageFactory
     */
    protected $helperFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ImageFactory $helperFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ImageFactory          $helperFactory,
        ScopeConfigInterface  $scopeConfig,
        StoreManagerInterface $storeManager,
        RequestInterface      $requestInterface
    )
    {
        $this->helperFactory = $helperFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->requestInterface = $requestInterface;
    }

    /**
     * Builds product image URL
     *
     * @param Product|ProductInterface $item
     * @return string
     * @throws NoSuchEntityException
     */
    public function getUrl($item, $scopeId = null)
    {
        $imageUrl = null;

        //Get image thumbnail from settings
        $imageType = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_IMAGE_TYPE, ScopeInterface::SCOPE_STORE);
        /** @var \Magento\Catalog\Helper\Image $helper */
        $helper = $this->helperFactory->create()->init($item, $imageType);

        if ($imageType) {
            $imageUrl = $helper->getUrl();
            if ($imageUrl == $helper->getDefaultPlaceholderUrl()) {
                // allow trying other types
                $imageUrl = null;
            }
        }

        if (!$imageUrl) {
            if ($scopeId) {
                $store = $this->storeManager->getStore($scopeId);
            } else {
                $store = $this->storeManager->getStore();
            }
            // Replace null coalescing operator with ternary operator for PHP 7.4 compatibility
            $itemImage = $item->getImage();
            if ($itemImage === null) {
                $itemImage = $item->getSmallImage();
                if ($itemImage === null) {
                    $itemImage = $item->getThumbnail();
                }
            }

            if ($itemImage === 'no_selection' || !$itemImage) {
                $imageUrl = $helper->getDefaultPlaceholderUrl('small_image');
            } else {
                $imageUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $itemImage;
            }
        }

        return $imageUrl;
    }
}
