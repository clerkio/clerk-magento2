<?php

namespace Clerk\Clerk\Helper;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Helper\Context as ContextHelper;
use Clerk\Clerk\Model\Config;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
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
     * @var ConfigHelper
     */
    protected $configHelper;
    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    /**
     * @param ConfigHelper $configHelper
     * @param ImageFactory $helperFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $requestInterface
     */
    public function __construct(
        ConfigHelper          $configHelper,
        ContextHelper         $contextHelper,
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
        $this->contextHelper = $contextHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Builds product image URL
     *
     * @param Product $item
     * @return string
     * @throws NoSuchEntityException
     */
    public function getUrl(Product $item)
    {
        $imageUrl = null;
        $imageType = $this->configHelper->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_IMAGE_TYPE);
        $helper = $this->helperFactory->create()->init($item, $imageType);

        if ($imageType) {
            $imageUrl = $helper->getUrl();
            if ($imageUrl == $helper->getDefaultPlaceholderUrl()) {
                $imageUrl = null;
            }
        }

        if (!$imageUrl) {
            $itemImage = $item->getImage() ?? $item->getSmallImage() ?? $item->getThumbnail();
            if ($itemImage === 'no_selection' || !$itemImage) {
                $imageUrl = $helper->getDefaultPlaceholderUrl('small_image');
            } else {
                $imageUrl = $this->contextHelper->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $itemImage;
            }
        }

        return $imageUrl;
    }
}
