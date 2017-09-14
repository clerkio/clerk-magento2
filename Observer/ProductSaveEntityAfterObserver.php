<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;

class ProductSaveEntityAfterObserver implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(ObjectManagerInterface $objectManager, ScopeConfigInterface $scopeConfig, Api $api)
    {
        $this->objectManager = $objectManager;
        $this->_scopeConfig = $scopeConfig;
        $this->api = $api;
    }

    /**
     * Add product to Clerk
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED)) {
            /** @var Product $product */
            $product = $observer->getProduct();

            if ($product && $product->getId()) {

                //Cancel if product visibility is not as defined
                if ($product->getVisibility() != $this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY)) {
                    return;
                }

                //Cancel if product is not saleable
                if ($this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY)) {
                    if (!$product->isSalable()) {
                        return;
                    }
                }

                $store = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
                $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();

                $productItem = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'price' => $product->getFinalPrice(),
                    'list_price' => $product->getPrice(),
                    'image' => $imageUrl,
                    'url' => $product->getUrlModel()->getUrl($product),
                    'categories' => $product->getCategoryIds(),
                    'sku' => $product->getSku(),
                    'on_sale' => ($product->getFinalPrice() < $product->getPrice()),
                ];

                /**
                 * @todo Refactor to use fieldhandlers or similar
                 */
                $configFields = $this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_FIELDS);
                $fields = explode(',', $configFields);

                foreach ($fields as $field) {
                    if (! isset($productItem[$field])) {
                        $productItem[$field] = $product->getData[$field];
                    }
                }

                $this->api->addProduct($productItem);
            }
        }
    }
}