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
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\App\ProductMetadataInterface;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as Bundle;

class Product extends AbstractAdapter
{

    const PRODUCT_TYPE_SIMPLE = 'simple';

    /**
    * @var LoggerInterface
    */
    protected $clerk_logger;

    /**
   * @var RequestInterface
   */
    protected $requestInterface;
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
     * @var StockStateInterface
     */
    protected $StockStateInterface;

    /**
     * @var StockItemRepository
     */
    protected $stockItemRepository;

    /**
     * @var ProductMetadataInterface
     */
    protected $ProductMetadataInterface;
    /**
     * Product constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param StockStateInterface $StockStateInterface
     * @param StockItemRepository $stockItemRepository
     * @param ProductMetadataInterface $ProductMetadataInterface
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        ClerkLogger $Clerklogger,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        Data $taxHelper,
        StockStateInterface $StockStateInterface,
        StockItemRepository $stockItemRepository,
        ProductMetadataInterface $ProductMetadataInterface,
        \Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->taxHelper = $taxHelper;
        $this->_stockFilter = $stockFilter;
        $this->clerk_logger = $Clerklogger;
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        $this->StockStateInterface = $StockStateInterface;
        $this->stockItemRepository = $stockItemRepository;
        $this->ProductMetadataInterface = $ProductMetadataInterface;
        $this->requestInterface = $requestInterface;
        parent::__construct(
            $scopeConfig,
            $eventManager,
            $storeManager,
            $collectionFactory,
            $Clerklogger
        );
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
            $productMetadata = $this->ProductMetadataInterface;
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
                case 'any':
                    $collection->addAttributeToFilter('visibility', ['in' => [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_IN_SEARCH, Visibility::VISIBILITY_BOTH]]);
                    break;
            }

            $collection->setPageSize($limit)->setCurPage($page)->addOrder($orderBy, $order);

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

            if ( isset($attribute) && !is_bool( $attribute ) && is_object( $attribute )  ) {
                if ($attribute->usesSource()) {
                    $source = $attribute->getSource();
                    if ($source) {
                        return $source->getOptionText($resourceItem[$field]);
                    }
                }
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

            $this->addFieldHandler('description_html', function ($item) {
                $description = $this->getAttributeValue($item, 'description') ? htmlentities($this->getAttributeValue($item, 'description'), ENT_QUOTES) : '';
                return $description;
            });

            $this->addFieldHandler('description', function ($item) {
                $description = $this->getAttributeValue($item, 'description') ? str_replace(array("\r", "\n"), ' ', strip_tags( html_entity_decode( $this->getAttributeValue($item, 'description') ) ) ) : '';
                return $description;
            });

            $this->addFieldHandler('visibility', function ($item) {
                return $item->getAttributeText('visibility');
            });

            //Add price fieldhandler
            $this->addFieldHandler('price', function ($item) {
                try {

                    $price = $this->getProductTaxPrice($item, $item->getFinalPrice(), true);

                    $item_type = $item->getTypeId();

                    switch($item_type) {
                        case Configurable::TYPE_CODE:
                            $childPrices = array();
                            $parentInstance = $item->getTypeInstance();
                            $childProducts = $parentInstance->getUsedProducts($item);
                            foreach ($childProducts as $child) {
                                $childPrices[] = (is_numeric($child->getFinalPrice()) && $child->getFinalPrice() > 0)  ? $child->getFinalPrice() : 0;
                            }
                            if(!empty($childPrices)) {
                                $price = min($childPrices) > 0 ? min($childPrices) : $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                                $price = $this->getProductTaxPrice($item, $price, true);
                            } else {
                                $price = $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                                $price = $this->getProductTaxPrice($item, $price, true);
                            }
                            break;
                        case Grouped::TYPE_CODE:
                            $associatedProducts = $item->getTypeInstance()->getAssociatedProducts($item);
                            if ( ! empty( $associatedProducts ) ) {
                                foreach ( $associatedProducts as $associatedProduct ) {
                                    if ( empty( $price ) ) {
                                        $price = $this->getProductTaxPrice($associatedProduct, $associatedProduct->getFinalPrice(), true);
                                        $price = str_replace(',', '', $price);
                                    } elseif ( $price > $associatedProduct->getPrice() ) {
                                        $price = $this->getProductTaxPrice($associatedProduct, $associatedProduct->getPrice(), true);
                                        $price = str_replace(',', '', $price);
                                    }
                                }
                            }
                            break;
                        case Bundle::TYPE_CODE:
                            $price = $item->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
                            $price = $this->getProductTaxPrice($item, $price, true);
                            break;
                        case self::PRODUCT_TYPE_SIMPLE:
                            $price = $this->getProductTaxPrice($item, $item->getFinalPrice(), true);
                            break;
                    }
                    return (float) number_format( (float) $price, 2, ".", "" );
                } catch (\Exception $e) {
                    return 0;
                }
            });

            $this->addFieldHandler('price_excl_tax', function ($item) {
                try {

                    $price = $this->getProductTaxPrice($item, $item->getFinalPrice(), false);

                    $item_type = $item->getTypeId();

                    switch($item_type) {
                        case Configurable::TYPE_CODE:
                            $childPrices = array();
                            $parentInstance = $item->getTypeInstance();
                            $childProducts = $parentInstance->getUsedProducts($item);
                            foreach ($childProducts as $child) {
                                $childPrices[] = (is_numeric($child->getFinalPrice()) && $child->getFinalPrice() > 0)  ? $child->getFinalPrice() : 0;
                            }
                            if(!empty($childPrices)) {
                                $price = min($childPrices) > 0 ? min($childPrices) : $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                                $price = $this->getProductTaxPrice($item, $price, false);
                            } else {
                                $price = $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                                $price = $this->getProductTaxPrice($item, $price, false);
                            }
                            break;
                        case Grouped::TYPE_CODE:
                            $associatedProducts = $item->getTypeInstance()->getAssociatedProducts($item);
                            if ( ! empty( $associatedProducts ) ) {
                                foreach ( $associatedProducts as $associatedProduct ) {
                                    if ( empty( $price ) ) {
                                        $price = $this->getProductTaxPrice($associatedProduct, $associatedProduct->getFinalPrice(), false);
                                        $price = str_replace(',', '', $price);
                                    } elseif ( $price > $associatedProduct->getPrice() ) {
                                        $price = $this->getProductTaxPrice($associatedProduct, $associatedProduct->getPrice(), false);
                                        $price = str_replace(',', '', $price);
                                    }
                                }
                            }
                            break;
                        case Bundle::TYPE_CODE:
                            $price = $item->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
                            $price = $this->getProductTaxPrice($item, $price, false);
                            break;
                        case self::PRODUCT_TYPE_SIMPLE:
                            $price = $this->getProductTaxPrice($item, $item->getFinalPrice(), false);
                            break;
                    }
                    return (float) number_format( (float) $price, 2, ".", "" );
                } catch (\Exception $e) {
                    return 0;
                }
            });

            //Add list_price fieldhandler
            $this->addFieldHandler('list_price', function ($item) {
                try {

                    $price = $this->getProductTaxPrice($item, $item->getPrice(), true);

                    $item_type = $item->getTypeId();

                    switch($item_type) {
                        case Configurable::TYPE_CODE:
                            $childPrices = array();
                            $parentInstance = $item->getTypeInstance();
                            $childProducts = $parentInstance->getUsedProducts($item);
                            foreach ($childProducts as $child) {
                                $childPrices[] = (is_numeric($child->getPrice()) && $child->getPrice() > 0)  ? $child->getPrice() : 0;
                            }
                            if(!empty($childPrices)) {
                                $price = min($childPrices) > 0 ? min($childPrices) : $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                                $price = $this->getProductTaxPrice($item, $price, true);
                            } else {
                                $price = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                                $price = $this->getProductTaxPrice($item, $price, true);
                            }
                            break;
                        case Grouped::TYPE_CODE:
                            $associatedProducts = $item->getTypeInstance()->getAssociatedProducts($item);
                            if (!empty($associatedProducts)) {
                                foreach ($associatedProducts as $associatedProduct) {
                                    if (empty($price)) {
                                        $price = $this->getProductTaxPrice($associatedProduct, $associatedProduct->getPrice(), true);
                                        $price = str_replace(',', '', $price);
                                    } elseif ($price > $associatedProduct->getPrice()) {
                                        $price = $this->getProductTaxPrice($associatedProduct, $associatedProduct->getPrice(), true);
                                        $price = str_replace(',', '', $price);
                                    }
                                }
                            }
                            break;
                        case Bundle::TYPE_CODE:
                            $price = $item->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
                            $price = $this->getProductTaxPrice($item, $price, true);
                            break;
                        case self::PRODUCT_TYPE_SIMPLE:
                            $price = $this->getProductTaxPrice($item, $item->getPrice(), true);
                            break;
                    }
                    return (float) number_format( (float) $price, 2, ".", "" );
                } catch (\Exception $e) {
                    return 0;
                }
            });

            $this->addFieldHandler('list_price_excl_tax', function ($item) {
                try {

                    $price = $this->getProductTaxPrice($item, $item->getPrice(), false);

                    $item_type = $item->getTypeId();

                    switch($item_type) {
                        case Configurable::TYPE_CODE:
                            $childPrices = array();
                            $parentInstance = $item->getTypeInstance();
                            $childProducts = $parentInstance->getUsedProducts($item);
                            foreach ($childProducts as $child) {
                                $childPrices[] = (is_numeric($child->getPrice()) && $child->getPrice() > 0)  ? $child->getPrice() : 0;
                            }
                            if(!empty($childPrices)) {
                                $price = min($childPrices) > 0 ? min($childPrices) : $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                                $price = $this->getProductTaxPrice($item, $price, false);
                            } else {
                                $price = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                                $price = $this->getProductTaxPrice($item, $price, false);
                            }
                            break;
                        case Grouped::TYPE_CODE:
                            $associatedProducts = $item->getTypeInstance()->getAssociatedProducts($item);
                            if (!empty($associatedProducts)) {
                                foreach ($associatedProducts as $associatedProduct) {
                                    if (empty($price)) {
                                        $price = $this->getProductTaxPrice($associatedProduct, $associatedProduct->getPrice(), false);
                                        $price = str_replace(',', '', $price);
                                    } elseif ($price > $associatedProduct->getPrice()) {
                                        $price = $this->getProductTaxPrice($associatedProduct, $associatedProduct->getPrice(), false);
                                        $price = str_replace(',', '', $price);
                                    }
                                }
                            }
                            break;
                        case Bundle::TYPE_CODE:
                            $price = $item->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
                            $price = $this->getProductTaxPrice($item, $price, false);
                            break;
                        case self::PRODUCT_TYPE_SIMPLE:
                            $price = $this->getProductTaxPrice($item, $item->getPrice(), false);
                            break;
                    }
                    return (float) number_format( (float) $price, 2, ".", "" );
                } catch (\Exception $e) {
                    return 0;
                }
            });

            $this->addFieldHandler('tier_price_values', function ($item) {
                $holderArray = [];
                $tierPriceObj = $item->getTierPrice();
                if (count($tierPriceObj) > 0) {
                    foreach ($tierPriceObj as $price) {
                        if (isset($price['price'])) {
                            array_push($holderArray, floatval($price['price']));
                        }
                    }
                }
                return $holderArray;
            });

            $this->addFieldHandler('tier_price_quantities', function ($item) {
                $holderArray = [];
                $tierPriceObj = $item->getTierPrice();
                if (count($tierPriceObj) > 0) {
                    foreach ($tierPriceObj as $price) {
                        if (isset($price['price_qty'])) {
                            array_push($holderArray, floatval($price['price_qty']));
                        }
                    }
                }
                return $holderArray;
            });

          //Add image fieldhandler
            $this->addFieldHandler('image', function ($item) {
                $imageUrl = $this->imageHelper->getUrl($item);

                /***
                 * Fix malformed image url's.
                 */
                $valid_path = strpos($imageUrl, 'catalog/product/');
                if ($valid_path > -1) {
                    return $imageUrl;
                } else {
                    $imageUrl = str_replace('catalog/product', 'catalog/product/', $imageUrl);
                    return $imageUrl;
                }


            });

            //Add url fieldhandler
            $this->addFieldHandler('url', function ($item) {
                $_params = $this->requestInterface->getParams();
                if (array_key_exists('scope_id', $_params)){
                    $storeId = $_params['scope_id'];
                } else {
                    $storeId = $this->storeManager->getStore()->getId();
                }
                return $item->setStoreId($storeId)->getUrlInStore();
            });

          //Add categories fieldhandler
            $this->addFieldHandler('categories', function ($item) {
                return $item->getCategoryIds();
            });

            $this->addFieldHandler('child_stocks', function ($item) {
                $productType = $item->getTypeID();
                $productTypeInstance = $item->getTypeInstance();
                $stock_list = [];
                switch ($productType) {
                    case 'configurable':
                            $usedProducts = $productTypeInstance->getUsedProducts($item);
                        foreach ($usedProducts as $simple) {
                            $stock_list[] = $this->getProductStockStateQty($simple);
                        }
                        break;
                    case 'grouped':
                            $usedProducts = $productTypeInstance->getAssociatedProducts($item);
                        foreach ($usedProducts as $simple) {
                            $stock_list[] = $this->getProductStockStateQty($simple);
                        }
                        break;
                }
                return $stock_list;
            });

            $this->addFieldHandler('stock', function ($item) {
                $productType = $item->getTypeID();
                $productTypeInstance = $item->getTypeInstance();
                $productStock = 0;

                switch ($productType) {
                    case 'configurable':
                        $sub_items = $productTypeInstance->getUsedProducts($item);
                        foreach ($sub_items as $sub_item) {
                            $productStock += $this->getProductStockStateQty($sub_item);
                        }
                        // If stock was 0, try to get it without looking at the scope.
                        if($productStock == 0){
                            foreach ($sub_items as $sub_item) {
                                $productStock += $this->getProductStockQty($sub_item);
                            }
                        }
                        break;
                    case 'simple':
                        $productStock = $this->getProductStockStateQty($item);
                        // If stock was 0, try to get it without looking at the scope.
                        if($productStock == 0){
                            $productStock = $this->getProductStockQty($item);
                        }
                        break;
                    case 'bundle':
                        // Not sure if this is still correct, with the deprecation of stock reguistry.
                        $productsArray = [];
                        $selectionCollection = $item->getTypeInstance(true)->getSelectionsCollection(
                            $item->getTypeInstance(true)->getOptionsIds($item),
                            $item
                        );

                        foreach ($selectionCollection as $proselection) {
                            $selectionArray = [];
                            $selectionArray['min_qty'] = $proselection->getSelectionQty();
                            $selectionArray['stock'] = $this->StockStateInterface->getStockQty($proselection->getProductId(), $item->getStore()->getWebsiteId());
                            $productsArray[$proselection->getOptionId()][$proselection->getSelectionId()] = $selectionArray;
                        }

                        $bundle_stock = 0;
                        foreach ($productsArray as $_ => $bundle_item) {
                            $bundle_option_min_stock = 0;
                            foreach ($bundle_item as $__ => $bundle_option) {
                                if ((integer)$bundle_option['min_qty'] <= $bundle_option['stock']) {
                                    $bundle_option_min_stock = ($bundle_option_min_stock == 0) ? $bundle_option['stock'] : $bundle_option_min_stock;
                                    $bundle_option_min_stock = ($bundle_option_min_stock < $bundle_option['stock']) ? $bundle_option['stock'] : $bundle_option_min_stock;
                                }
                            }
                            $bundle_stock = ($bundle_stock == 0) ? $bundle_option_min_stock : $bundle_stock;
                            $bundle_stock = ($bundle_stock < $bundle_option_min_stock) ? $bundle_option_min_stock : $bundle_stock;
                        }

                        $productStock = $bundle_stock;
                        break;
                    case 'grouped':
                        $sub_items = $productTypeInstance->getAssociatedProducts($item);
                        foreach ($sub_items as $sub_item) {
                            $productStock += $this->getProductStockStateQty($sub_item);
                        }
                        // If stock was 0, try to get it without looking at the scope.
                        if($productStock == 0){
                            foreach ($sub_items as $sub_item) {
                                $productStock += $this->getProductStockQty($sub_item);
                            }
                        }
                        break;
                }

                return $productStock;
            });

            //Add age fieldhandler
            $this->addFieldHandler('age', function ($item) {
                $createdAt = strtotime($item->getCreatedAt());
                $now = time();
                $diff = $now - $createdAt;
                return floor($diff / (60 * 60 * 24));
            });

            //Add created_at fieldhandler
            $this->addFieldHandler('created_at', function ($item) {
                $createdAt = strtotime($item->getCreatedAt());
                return $createdAt;
            });

            $this->addFieldHandler('product_type', function ($item) {
                $type = $item->getTypeId();
                return $type;
            });

            $this->addFieldHandler('manufacturer', function ($item) {
                $brand = $this->getAttributeValue($item, 'manufacturer');
                return $brand;
            });

            //Add on_sale fieldhandler
            $this->addFieldHandler('on_sale', function ($item) {
                return false;
            });

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Field Handlers Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get Product stock from item repository
     */

     protected function getProductStockQty($product){
        $stock_info = $this->stockItemRepository->get($product->getId());
        if(!empty($stock_info)){
            return $stock_info->getQty();
        } else {
            return 0;
        }
    }

    /**
     * Get Product stock from interface
     */
    protected function getProductStockStateQty($product){
        $product_stock = $this->StockStateInterface->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
        if(isset($product_stock)){
            return $product_stock;
        } else {
            return 0;
        }
    }

    /**
     * Get Product price with contextual taxes
     */

    protected function getProductTaxPrice($product, $price, $withTax=true){
        $request = $this->requestInterface->getParams();
        if (array_key_exists('scope_id', $request)){
            $store = $this->storeManager->getStore($request['scope_id']);
        } else {
            $store = $this->storeManager->getStore();
        }

        return $this->taxHelper->getTaxPrice($product, $price, $withTax, null, null, null, $store, null, true);
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
            'price_excl_tax',
            'list_price',
            'list_price_excl_tax',
            'image',
            'url',
            'categories',
            'manufacturer',
            'sku',
            'age',
            'created_at',
            'on_sale',
            'stock',
            'product_type',
            'tier_price_values',
            'tier_price_quantities',
            'child_stocks'
            ];

            $additionalFields = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS, $scope, $scopeid);

            if ($additionalFields) {
                $fields = array_merge($fields, str_replace(' ', '', explode(',', $additionalFields)));
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
