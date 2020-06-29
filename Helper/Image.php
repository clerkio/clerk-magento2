<?php

namespace Clerk\Clerk\Helper;

use Clerk\Clerk\Model\Config;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

class Image
{
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
        ImageFactory $helperFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->helperFactory = $helperFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Builds product image URL
     *
     * @param Product $item
     * @return string
     */
    public function getUrl(Product $item)
    {
        $imageUrl = null;

        //Get image thumbnail from settings
        $imageType = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_IMAGE_TYPE, ScopeInterface::SCOPE_STORE);
        $helper = $this->helperFactory->create();

        if ($imageType) {
            /** @var \Magento\Catalog\Helper\Image $helper */
            $imageUrl = $helper->init($item, $imageType)->getUrl();;
            if ($imageUrl == $helper->getDefaultPlaceholderUrl()) {
                // allow to try other types
                $imageUrl = null;
            }
        }

        if (!$imageUrl) {
            $store = $this->storeManager->getStore();
            $itemImage = $item->getImage() ?? $item->getSmallImage() ?? $item->getThumbnail();

            if ($itemImage === 'no_selection') {
                $imageUrl = $helper->getDefaultPlaceholderUrl('small_image');
            } else {
                $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $itemImage;
            }
        }

        return $imageUrl;
    }
}
