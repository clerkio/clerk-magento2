<?php

namespace Clerk\Clerk\Model\Adapter;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Helper\Image;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Bundle\Model\Product\Type as Bundle;

class Product extends AbstractAdapter
{
    /**
     * @var LoggerInterface
     */
    protected $clerk_logger;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var string
     */
    protected $eventPrefix = 'product';
    /**
     * @var
     */
    protected $_stockFilter;

    /**
     * @var
     */
    protected $storeManager;

    /**
     * @var
     */
    protected $taxHelper;

    /**
     * @var array
     */
    protected $fieldMap = [
        'entity_id' => 'id',
    ];

    /**
     * Product constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        ClerkLogger $Clerklogger,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        Data $taxHelper

    )
    {
        $this->taxHelper = $taxHelper;
        $this->_stockFilter = $stockFilter;
        $this->clerk_logger = $Clerklogger;
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        parent::__construct($scopeConfig, $eventManager, $storeManager, $collectionFactory, $Clerklogger);
    }

    /**
     * Prepare collection
     *
     * @return mixed
     */
    protected function prepareCollection($page, $limit, $orderBy, $order, $scope, $scopeid)
    {
        try {

            $collection = $this->collectionFactory->create();

            $collection->addFieldToSelect('*');
            $collection->addStoreFilter($scopeid);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $version = $productMetadata->getVersion();

            if (!$version >= '2.3.3') {

                //Filter on is_saleable if defined
                if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, $scope, $scopeid)) {
                    $this->_stockFilter->addInStockFilterToCollection($collection);
                }


            } else {

                if (!$this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, $scope, $scopeid)) {
                    $collection->setFlag('has_stock_status_filter', true);
                }

            }

            $visibility = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY, $scope, $scopeid);

            switch ($visibility) {
                case Visibility::VISIBILITY_IN_CATALOG:
                    $collection->setVisibility([Visibility::VISIBILITY_IN_CATALOG]);
                    break;
                case Visibility::VISIBILITY_IN_SEARCH:
                    $collection->setVisibility([Visibility::VISIBILITY_IN_SEARCH]);
                    break;
                case Visibility::VISIBILITY_BOTH:
                    $collection->setVisibility([Visibility::VISIBILITY_BOTH]);
                    break;
            }

            $collection->setPageSize($limit)
                ->setCurPage($page)
                ->addOrder($orderBy, $order);

            $this->eventManager->dispatch('clerk_' . $this->eventPrefix . '_get_collection_after', [
                'adapter' => $this,
                'collection' => $collection
            ]);

            return $collection;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Prepare Collection Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get attribute value for product
     *
     * @param $resourceItem
     * @param $field
     * @return mixed
     */
    protected function getAttributeValue($resourceItem, $field)
    {
        try {

            $attribute = $resourceItem->getResource()->getAttribute($field);

            if ($attribute->usesSource()) {
                return $attribute->getSource()->getOptionText($resourceItem[$field]);
            }

            return parent::getAttributeValue($resourceItem, $field);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Attribute Value Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Add field handlers for products
     */
    protected function addFieldHandlers()
    {

        try {

            //Add price fieldhandler
            $this->addFieldHandler('price', function ($item) {
                try {

                    $price = $this->taxHelper->getTaxPrice($item, $item->getFinalPrice(), true);

                    //Fix for configurable products
                    if ($item->getTypeId() === Configurable::TYPE_CODE) {
                        $price = $this->taxHelper->getTaxPrice($item, $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(), true);
                    }

                    //Fix for Grouped products
                    if ($item->getTypeId() === "grouped") {
                        $associatedProducts = $item->getTypeInstance()->getAssociatedProducts($item);

                        if (!empty($associatedProducts)) {

                            foreach ($associatedProducts as $associatedProduct) {

                                if (empty($price)) {

                                    $price = $this->taxHelper->getTaxPrice($associatedProduct, $associatedProduct->getFinalPrice(), true);
                                    $price = str_replace(',','', $price);


                                } elseif ($price > $associatedProduct->getPrice()) {

                                    $price = $this->taxHelper->getTaxPrice($associatedProduct, $associatedProduct->getFinalPrice(), true);
                                    $price = str_replace(',','', $price);

                                }
                            }
                        }
                    }

                    if ($item->getTypeId() === Bundle::TYPE_CODE) {
                        //Get minimum price for bundle products
                        $price = $item
                            ->getPriceInfo()
                            ->getPrice('final_price')
                            ->getMinimalPrice()
                            ->getValue();

                    }

                    if ($item->getTypeId() === 'simple') {
                        $price = $this->taxHelper->getTaxPrice($item, $item->getFinalPrice(), true);
                    }

                    return number_format( (float)$price, 2 );
                } catch (\Exception $e) {
                    return 0;
                }
            });

            //Add list_price fieldhandler
            $this->addFieldHandler('list_price', function ($item) {
                try {

                    $price = $this->taxHelper->getTaxPrice($item, $item->getPrice(), true);

                    //Fix for configurable products
                    if ($item->getTypeId() === Configurable::TYPE_CODE) {
                        $price = $this->taxHelper->getTaxPrice($item, $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), true);
                    }

                    //Fix for Grouped products
                    if ($item->getTypeId() === "grouped") {
                        $associatedProducts = $item->getTypeInstance()->getAssociatedProducts($item);

                        if (!empty($associatedProducts)) {

                            foreach ($associatedProducts as $associatedProduct) {

                                if (empty($price)) {

                                    $price = $this->taxHelper->getTaxPrice($associatedProduct, $associatedProduct->getPrice(), true);
                                    $price = str_replace(',','', $price);


                                } elseif ($price > $associatedProduct->getPrice()) {

                                    $price = $this->taxHelper->getTaxPrice($associatedProduct, $associatedProduct->getPrice(), true);
                                    $price = str_replace(',','', $price);

                                }
                            }
                        }
                    }

                    if ($item->getTypeId() === Bundle::TYPE_CODE) {
                        $price = $item
                            ->getPriceInfo()
                            ->getPrice('regular_price')
                            ->getMinimalPrice()
                            ->getValue();
                    }

                    if ($item->getTypeId() === 'simple') {
                        $price = $this->taxHelper->getTaxPrice($item, $item->getPrice(), true);
                    }

                    return number_format( (float)$price, 2);
                } catch (\Exception $e) {
                    return 0;
                }
            });

            //Add image fieldhandler
            $this->addFieldHandler('image', function ($item) {
                $imageUrl = $this->imageHelper->getUrl($item);

                return $imageUrl;
            });

            //Add url fieldhandler
            $this->addFieldHandler('url', function ($item) {
                return $item->setStoreId($this->storeManager->getStore()->getId())->getUrlInStore();
            });

            //Add categories fieldhandler
            $this->addFieldHandler('categories', function ($item) {
                return $item->getCategoryIds();
            });

            //Add stock fieldhandler
            $this->addFieldHandler('stock', function ($item) {
                $productType = $item->getTypeID();
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                $total_stock = 0;

                if($productType == 'configurable'){

                    $productTypeInstance = $item->getTypeInstance();
                    $usedProducts = $productTypeInstance->getUsedProducts($item);
                    foreach ($usedProducts as $simple) {
                        $total_stock += $StockState->getStockQty($simple->getId(), $simple->getStore()->getWebsiteId());
                    }

                } else {

                    $total_stock = $StockState->getStockQty($item->getId(), $item->getStore()->getWebsiteId());

                }

                return $total_stock;
            });

            //Add age fieldhandler
            $this->addFieldHandler('age', function ($item) {
                $createdAt = strtotime($item->getCreatedAt());
                $now = time();
                $diff = $now - $createdAt;
                return floor($diff / (60 * 60 * 24));
            });

            //Add on_sale fieldhandler
            $this->addFieldHandler('on_sale', function ($item) {
                try {
                    $price = $item->getPrice();
                    $finalPrice = $item->getFinalPrice();
                    //Fix for configurable products
                    if ($item->getTypeId() === Configurable::TYPE_CODE) {
                        $price = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                        $finalPrice = $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                    }

                    if ($item->getTypeId() === Bundle::TYPE_CODE) {
                        $price = $item
                            ->getPriceInfo()
                            ->getPrice('regular_price')
                            ->getMinimalPrice()
                            ->getValue();

                        $finalPrice = $item
                            ->getPriceInfo()
                            ->getPrice('final_price')
                            ->getMinimalPrice()
                            ->getValue();
                    }

                    return $finalPrice < $price;
                } catch (\Exception $e) {
                    return false;
                }
            });

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Field Handlers Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get default product fields
     *
     * @return array
     */
    protected function getDefaultFields($scope, $scopeid)
    {

        try {

            $fields = [
                'name',
                'description',
                'price',
                'list_price',
                'image',
                'url',
                'categories',
                'brand',
                'sku',
                'age',
                'on_sale',
                'stock'
            ];

            $additionalFields = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS, $scope, $scopeid);

            if ($additionalFields) {
                $fields = array_merge($fields, str_replace(' ','' ,explode(',', $additionalFields)));
            }

            foreach ($fields as $key => $field) {

                $fields[$key] = $field;

            }

            return $fields;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Default Fields Error', ['error' => $e->getMessage()]);

        }
    }
}
