<?php

namespace Clerk\Clerk\Model\Adapter;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Helper\Image;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\CatalogInventory\Helper\Stock as StockFilter;
use Magento\Inventory\Model\SourceItem\Command\GetSourceItemsBySku as ItemSource;
use Magento\Tax\Model\Calculation\Rate as TaxRate;

class Product extends AbstractAdapter
{

  const PRODUCT_TYPE_SIMPLE = 'simple';
  const PRODUCT_TYPE_CONFIGURABLE = 'configurable';
  const PRODUCT_TYPE_GROUPED = 'grouped';
  const PRODUCT_TYPE_BUNDLE = 'bundle';
  const PRODUCT_TYPES = [
    self::PRODUCT_TYPE_SIMPLE,
    self::PRODUCT_TYPE_CONFIGURABLE,
    self::PRODUCT_TYPE_GROUPED,
    self::PRODUCT_TYPE_BUNDLE
  ];


  /**
   * @var TaxRate;
   */
  protected $taxRate;


  /**
   * @var null
   */
  protected $productTaxRates;

  /**
   * @var ItemSource
   */
  protected $itemSource;

  /**
   * @var StockFilter
   */
  protected $stockFilter;

  /**
   * @var GetSalableQuantityDataBySku
   */
  protected $getSalableQuantityDataBySku;

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
   * @var StockStateInterface
   */
  protected $stockStateInterface;

  /**
   * @var ProductMetadataInterface
   */
  protected $productMetadataInterface;

  /**
   * @var string
   */
  protected $eventPrefix = 'product';

  /**
   * @var StoreManagerInterface
   */
  protected $storeManager;

  /**
   * @var Data
   */
  protected $taxHelper;

  /**
   * @var array
   */
  protected $fieldMap = [
    'entity_id' => 'id',
  ];

  /**
   * Summary of __construct
   * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
   * @param \Magento\Framework\Event\ManagerInterface $eventManager
   * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
   * @param \Magento\Store\Model\StoreManagerInterface $storeManager
   * @param \Clerk\Clerk\Helper\Image $imageHelper
   * @param \Clerk\Clerk\Controller\Logger\ClerkLogger $clerkLogger
   * @param \Magento\CatalogInventory\Helper\Stock $stockFilter
   * @param \Magento\Catalog\Helper\Data $taxHelper
   * @param \Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface
   * @param \Magento\Framework\App\ProductMetadataInterface $productMetadataInterface
   * @param \Magento\Framework\App\RequestInterface $requestInterface
   * @param \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku
   * @param \Magento\Tax\Model\Calculation\Rate $taxRate
   */
  public function __construct(
    ScopeConfigInterface $scopeConfig,
    ManagerInterface $eventManager,
    CollectionFactory $collectionFactory,
    StoreManagerInterface $storeManager,
    Image $imageHelper,
    ClerkLogger $clerkLogger,
    StockFilter $stockFilter,
    Data $taxHelper,
    StockStateInterface $stockStateInterface,
    ProductMetadataInterface $productMetadataInterface,
    RequestInterface $requestInterface,
    GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
    ItemSource $itemSource,
    TaxRate $taxRate
  ) {
    $this->taxHelper = $taxHelper;
    $this->stockFilter = $stockFilter;
    $this->clerk_logger = $clerkLogger;
    $this->imageHelper = $imageHelper;
    $this->storeManager = $storeManager;
    $this->stockStateInterface = $stockStateInterface;
    $this->productMetadataInterface = $productMetadataInterface;
    $this->requestInterface = $requestInterface;
    $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
    $this->itemSource = $itemSource;
    $this->taxRate = $taxRate;
    $this->productTaxRates = $this->taxRate->getCollection()->getData();
    parent::__construct(
      $scopeConfig,
      $eventManager,
      $storeManager,
      $collectionFactory,
      $clerkLogger
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
      $productMetadata = $this->productMetadataInterface;
      $version = $productMetadata->getVersion();

      if (!$version >= '2.3.3') {

        //Filter on is_saleable if defined
        if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, $scope, $scopeid)) {
          $this->stockFilter->addInStockFilterToCollection($collection);
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

      $attributeResource = $resourceItem->getResource();

      if( ! $attributeResource ) {
        return parent::getAttributeValue($resourceItem, $field);
      }

      $attribute = $attributeResource->getAttribute($field);

      if ( !is_bool( $attribute ) && is_object( $attribute )  ) {
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

      //Add age fieldhandler
      $this->addFieldHandler('age', function ($item) {
        return floor( ( time() - strtotime( $item->getCreatedAt() ) ) / (60 * 60 * 24) );
      });

      //Add created_at fieldhandler
      $this->addFieldHandler('created_at', function ($item) {
        return strtotime($item->getCreatedAt());
      });

      $this->addFieldHandler('product_type', function ($item) {
        return $item->getTypeId();
      });

      $this->addFieldHandler('manufacturer', function ($item) {
        return $this->getAttributeValue($item, 'manufacturer');
      });

      $this->addFieldHandler('description_html', function ($item) {
        return $this->getAttributeValue($item, 'description') ? htmlentities($this->getAttributeValue($item, 'description'), ENT_QUOTES) : '';
      });

      $this->addFieldHandler('description', function ($item) {
        return $this->getAttributeValue($item, 'description') ? str_replace(array("\r", "\n"), ' ', strip_tags( html_entity_decode( $this->getAttributeValue($item, 'description') ) ) ) : '';
      });

      $this->addfieldhandler('visibility', function ($item) {
        return $item->getattributetext('visibility');
      });

      $this->addFieldHandler('tax_rate', function ($item) {
        foreach( $this->productTaxRates as $tax ){
          if ( array_key_exists( 'tax_calculation_rate_id', $tax ) && $item->getTaxClassId() == $tax['tax_calculation_rate_id'] ){
            return (float) $tax['rate'];
          }
        }
        return 0;
      });

      $this->addFieldHandler('price', function ($item) {
        try {

          $productType = $item->getTypeId();
          $productTypeInstance = $item->getTypeInstance();

          if($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES) ){
            return $this->formatPrice( $this->getProductTaxPrice($item, $item->getFinalPrice(), true) );
          }
          if($productType == self::PRODUCT_TYPE_GROUPED){
            $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
            $groupedProductPriceTotal = 0;
            if ( ! empty( $associatedProducts ) ) {
              foreach ( $associatedProducts as $associatedProduct ) {
                $associatedProductQuantity = $associatedProduct->getQty();
                $associatedProductPriceSource = null;
                if($associatedProduct->getFinalPrice()){
                  $associatedProductPriceSource = $associatedProduct->getFinalPrice();
                }elseif($associatedProduct->getPrice()){
                  $associatedProductPriceSource = $associatedProduct->getPrice();
                }
                if( isset($associatedProductPriceSource) && $associatedProductQuantity ){
                  $groupedProductPriceTotal += $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProductPriceSource, true)) * $associatedProductQuantity;
                }
              }
            }
            return $groupedProductPriceTotal;
          }

          if($productType == self::PRODUCT_TYPE_BUNDLE){
            return $this->formatPrice( $item->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue() );
          }
          if($productType == self::PRODUCT_TYPE_CONFIGURABLE){
            $childPrices = array();
            $childProducts = $productTypeInstance->getUsedProducts($item);
            if( ! empty($childProducts) ){
              foreach( $childProducts as $childProduct ){
                if(is_numeric($childProduct->getFinalPrice()) && $childProduct->getFinalPrice() > 0){
                  $childPrices[] = $childProduct->getFinalPrice();
                }
              }
            }
            if( ! empty($childPrices) ){
              return $this->formatPrice( $this->getProductTaxPrice( $item, min($childPrices), true ) );
            } else {
              return $this->formatPrice( $this->getProductTaxPrice( $item, $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(), true ) );
            }
          }
        } catch (\Exception $e) {
          $this->clerk_logger->error('Getting Product Price Error', ['error' => $e->getMessage()]);
        }
      });

      $this->addFieldHandler('price_excl_tax', function ($item) {
        try {

          $productType = $item->getTypeId();
          $productTypeInstance = $item->getTypeInstance();

          if($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES) ){
            return $this->formatPrice( $this->getProductTaxPrice($item, $item->getFinalPrice(), false) );
          }
          if($productType == self::PRODUCT_TYPE_GROUPED){
            $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
            $groupedProductPriceTotal = 0;
            if ( ! empty( $associatedProducts ) ) {
              foreach ( $associatedProducts as $associatedProduct ) {
                $associatedProductQuantity = $associatedProduct->getQty();
                $associatedProductPriceSource = null;
                if($associatedProduct->getFinalPrice()){
                  $associatedProductPriceSource = $associatedProduct->getFinalPrice();
                }elseif($associatedProduct->getPrice()){
                  $associatedProductPriceSource = $associatedProduct->getPrice();
                }
                if( isset($associatedProductPriceSource) && $associatedProductQuantity ){
                  $groupedProductPriceTotal += $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProductPriceSource, false)) * $associatedProductQuantity;
                }
              }
            }
            return $groupedProductPriceTotal;
          }

          if($productType == self::PRODUCT_TYPE_BUNDLE){
            return $this->formatPrice( $item->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue() );
          }
          if($productType == self::PRODUCT_TYPE_CONFIGURABLE){
            $childPrices = array();
            $childProducts = $productTypeInstance->getUsedProducts($item);
            if( ! empty($childProducts) ){
              foreach( $childProducts as $childProduct ){
                if(is_numeric($childProduct->getFinalPrice()) && $childProduct->getFinalPrice() > 0){
                  $childPrices[] = $childProduct->getFinalPrice();
                }
              }
            }
            if( ! empty($childPrices) ){
              return $this->formatPrice( $this->getProductTaxPrice( $item, min($childPrices), false ) );
            } else {
              return $this->formatPrice( $this->getProductTaxPrice( $item, $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(), false ) );
            }
          }
        } catch (\Exception $e) {
          $this->clerk_logger->error('Getting Product Price Exc Tax Error', ['error' => $e->getMessage()]);
        }
      });

      //Add list_price fieldhandler
      $this->addFieldHandler('list_price', function ($item) {
        try {

          $productType = $item->getTypeId();
          $productTypeInstance = $item->getTypeInstance();

          if($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES) ){
            return $this->formatPrice( $this->getProductTaxPrice($item, $item->getPrice(), true) );
          }
          if($productType == self::PRODUCT_TYPE_GROUPED){
            $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
            $groupedProductPriceTotal = 0;
            if ( ! empty( $associatedProducts ) ) {
              foreach ( $associatedProducts as $associatedProduct ) {
                $associatedProductQuantity = $associatedProduct->getQty();
                $associatedProductPriceSource = null;
                if($associatedProduct->getPrice()){
                  $associatedProductPriceSource = $associatedProduct->getPrice();
                }
                if( isset($associatedProductPriceSource) && $associatedProductQuantity ){
                  $groupedProductPriceTotal += $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProductPriceSource, true)) * $associatedProductQuantity;
                }
              }
            }
            return $groupedProductPriceTotal;
          }

          if($productType == self::PRODUCT_TYPE_BUNDLE){
            return $this->formatPrice( $item->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue() );
          }
          if($productType == self::PRODUCT_TYPE_CONFIGURABLE){
            $childPrices = array();
            $childProducts = $productTypeInstance->getUsedProducts($item);
            if( ! empty($childProducts) ){
              foreach( $childProducts as $childProduct ){
                if(is_numeric($childProduct->getPrice()) && $childProduct->getPrice() > 0){
                  $childPrices[] = $childProduct->getPrice();
                }
              }
            }
            if( ! empty($childPrices) ){
              return $this->formatPrice( $this->getProductTaxPrice( $item, min($childPrices), true ) );
            } else {
              return $this->formatPrice( $this->getProductTaxPrice( $item, $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), true ) );
            }
          }
        } catch (\Exception $e) {
          $this->clerk_logger->error('Getting Product List Price Error', ['error' => $e->getMessage()]);
        }
      });

      $this->addFieldHandler('list_price_excl_tax', function ($item) {
        try {

          $productType = $item->getTypeId();
          $productTypeInstance = $item->getTypeInstance();

          if($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES) ){
            return $this->formatPrice( $this->getProductTaxPrice($item, $item->getPrice(), false) );
          }
          if($productType == self::PRODUCT_TYPE_GROUPED){
            $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
            $groupedProductPriceTotal = 0;
            if ( ! empty( $associatedProducts ) ) {
              foreach ( $associatedProducts as $associatedProduct ) {
                $associatedProductQuantity = $associatedProduct->getQty();
                $associatedProductPriceSource = null;
                if($associatedProduct->getPrice()){
                  $associatedProductPriceSource = $associatedProduct->getPrice();
                }
                if( isset($associatedProductPriceSource) && $associatedProductQuantity ){
                  $groupedProductPriceTotal += $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProductPriceSource, false)) * $associatedProductQuantity;
                }
              }
            }
            return $groupedProductPriceTotal;
          }

          if($productType == self::PRODUCT_TYPE_BUNDLE){
            return $this->formatPrice( $item->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue() );
          }
          if($productType == self::PRODUCT_TYPE_CONFIGURABLE){
            $childPrices = array();
            $childProducts = $productTypeInstance->getUsedProducts($item);
            if( ! empty($childProducts) ){
              foreach( $childProducts as $childProduct ){
                if(is_numeric($childProduct->getPrice()) && $childProduct->getPrice() > 0){
                  $childPrices[] = $childProduct->getPrice();
                }
              }
            }
            if( ! empty($childPrices) ){
              return $this->formatPrice( $this->getProductTaxPrice( $item, min($childPrices), false ) );
            } else {
              return $this->formatPrice( $this->getProductTaxPrice( $item, $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), false ) );
            }
          }
        } catch (\Exception $e) {
          $this->clerk_logger->error('Getting Product List Price Exc Tax Error', ['error' => $e->getMessage()]);
        }
      });

      $this->addFieldHandler('tier_price_values', function ($item) {
        $tierPriceValues = array();
        $tierPrices = $item->getTierPrice();
        if( ! empty($tierPrices) ){
          foreach ($tierPrices as $tierPrice) {
            if (isset($tierPrice['price'])) {
              $tierPriceValues[] = $this->formatPrice($tierPrice['price']);
            }
          }
        }
        return $tierPriceValues;
      });

      $this->addFieldHandler('tier_price_quantities', function ($item) {
        $tierPriceQuantities = array();
        $tierPrices = $item->getTierPrice();
        if( ! empty($tierPrices) ){
          foreach ($tierPrices as $tierPrice) {
            if (isset($tierPrice['price_qty'])) {
              $tierPriceQuantities[] = (int) $tierPrice['price_qty'];
            }
          }
        }
        return $tierPriceQuantities;
      });

      //Add image fieldhandler
      $this->addFieldHandler('image', function ($item) {
        return $this->fixImagePath($this->imageHelper->getUrl($item));
      });

      //Add url fieldhandler
      $this->addFieldHandler('url', function ($item) {
        $storeId = $this->getStoreIdFromContext();
        return $item->setStoreId($storeId)->getUrlInStore();
      });

      //Add categories fieldhandler
      $this->addFieldHandler('categories', function ($item) {
        return $item->getCategoryIds();
      });

      $this->addFieldHandler('child_stocks', function ($item) {
        $productType = $item->getTypeID();
        $productTypeInstance = $item->getTypeInstance();
        $stockValues = array();
        if($productType ==  self::PRODUCT_TYPE_CONFIGURABLE){
          $usedProducts = $productTypeInstance->getUsedProducts($item);
          foreach($usedProducts as $usedProduct){
            $stockValues[] = $this->getProductStockStateQty($usedProduct);
          }
        }
        if($productType == self::PRODUCT_TYPE_GROUPED){
          $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
          foreach($associatedProducts as $associatedProduct){
            $stockValues[] = $this->getProductStockStateQty($associatedProduct);
          }
        }
        return $stockValues;
      });

      $this->addFieldHandler('child_prices', function ($item) {
        $productType = $item->getTypeID();
        $productTypeInstance = $item->getTypeInstance();
        $childPrices = array();
        if($productType == self::PRODUCT_TYPE_CONFIGURABLE){
          $usedProducts = $productTypeInstance->getUsedProducts($item);
          foreach($usedProducts as $usedProduct){
            $childPrices[] = $this->formatPrice( $this->getProductTaxPrice( $usedProduct, $usedProduct->getFinalPrice(), true ) );
          }
        }
        if($productType == self::PRODUCT_TYPE_GROUPED){
          $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
          foreach($associatedProducts as $associatedProduct){
            $childPrices[] = $this->formatPrice( $this->getProductTaxPrice( $associatedProduct, $associatedProduct->getFinalPrice(), true ) );
          }
        }
        return $childPrices;
      });

      $this->addFieldHandler('child_list_prices', function ($item) {
        $productType = $item->getTypeID();
        $productTypeInstance = $item->getTypeInstance();
        $childPrices = array();
        if($productType == self::PRODUCT_TYPE_CONFIGURABLE){
          $usedProducts = $productTypeInstance->getUsedProducts($item);
          foreach($usedProducts as $usedProduct){
            $childPrices[] = $this->formatPrice( $this->getProductTaxPrice( $usedProduct, $usedProduct->getPrice(), true ) );
          }
        }
        if($productType == self::PRODUCT_TYPE_GROUPED){
          $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
          foreach($associatedProducts as $associatedProduct){
            $childPrices[] = $this->formatPrice( $this->getProductTaxPrice( $associatedProduct, $associatedProduct->getPrice(), true ) );
          }
        }
        return $childPrices;
      });

      $this->addFieldHandler('child_images', function ($item) {
        $productType = $item->getTypeID();
        $productTypeInstance = $item->getTypeInstance();
        $childImages = array();
        if($productType == self::PRODUCT_TYPE_CONFIGURABLE){
          $usedProducts = $productTypeInstance->getUsedProducts($item);
          foreach($usedProducts as $usedProduct){
            $childImages[] = $this->fixImagePath($this->imageHelper->getUrl($usedProduct));
          }
        }
        if($productType == self::PRODUCT_TYPE_GROUPED){
          $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
          foreach($associatedProducts as $associatedProduct){
            $childImages[] = $this->fixImagePath($this->imageHelper->getUrl($associatedProduct));
          }
        }
        return $childImages;
      });

      $this->addFieldHandler('stock', function ($item) {
        $productType = $item->getTypeID();
        $productTypeInstance = $item->getTypeInstance();

        $productStock = 0;

        if($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES)){
          $productStock = $this->getProductStockStateQty($item);
          // If stock was 0, try to get it without looking at the scope.
          if($productStock == 0){
            $productStock = $this->getSaleableStockBySku($item->getSku());
          }
        }
        if($productType == self::PRODUCT_TYPE_CONFIGURABLE){
          $usedProducts = $productTypeInstance->getUsedProducts($item);
          foreach($usedProducts as $usedProduct){
            $productStock += $this->getProductStockStateQty($usedProduct);
          }
          if($productStock == 0){
            foreach ($usedProducts as $usedProduct) {
              $productStock += $this->getSaleableStockBySku($usedProduct->getSku());
            }
          }
        }
        if($productType == self::PRODUCT_TYPE_GROUPED){
          $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
          foreach($associatedProducts as $associatedProduct){
            $productStock += $this->getProductStockStateQty($associatedProduct);
          }
          if($productStock == 0){
            foreach($associatedProducts as $associatedProduct){
              $productStock += $this->getSaleableStockBySku($associatedProduct->getSku());
            }
          }
        }
        if($productType == self::PRODUCT_TYPE_BUNDLE){
          $productsArray = array();
          $selectionCollection = $item->getTypeInstance(true)->getSelectionsCollection(
            $item->getTypeInstance(true)->getOptionsIds($item),
            $item
          );

          foreach ($selectionCollection as $proselection) {
            $selectionArray = array();
            $selectionArray['min_qty'] = $proselection->getSelectionQty();
            $selectionArray['stock'] = $this->stockStateInterface->getStockQty($proselection->getProductId(), $item->getStore()->getWebsiteId());
            $productsArray[$proselection->getOptionId()][$proselection->getSelectionId()] = $selectionArray;
          }

          $bundle_stock = 0;
          foreach ($productsArray as $bundle_item) {
            $bundle_option_min_stock = 0;
            foreach ($bundle_item as $bundle_option) {
              if ((integer)$bundle_option['min_qty'] <= $bundle_option['stock']) {
                $bundle_option_min_stock = ($bundle_option_min_stock == 0) ? $bundle_option['stock'] : $bundle_option_min_stock;
                $bundle_option_min_stock = ($bundle_option_min_stock < $bundle_option['stock']) ? $bundle_option['stock'] : $bundle_option_min_stock;
              }
            }
            $bundle_stock = ($bundle_stock == 0) ? $bundle_option_min_stock : $bundle_stock;
            $bundle_stock = ($bundle_stock < $bundle_option_min_stock) ? $bundle_option_min_stock : $bundle_stock;
          }
          $productStock = $bundle_stock;
        }
        return $productStock;
      });

      $this->addFieldHandler('multi_source_stock', function ($item) {
        $productType = $item->getTypeID();
        $productTypeInstance = $item->getTypeInstance();
        $productStock = 0;

        if( $productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES) ){
          $productStock = $this->getSourceStockBySku($item->getSku());
        }
        if( $productType == self::PRODUCT_TYPE_CONFIGURABLE){
          $usedProducts = $productTypeInstance->getUsedProducts($item);
          foreach($usedProducts as $usedProduct){
            $productStock += $this->getSourceStockBySku($usedProduct->getSku());
          }
        }
        if( $productType == self::PRODUCT_TYPE_GROUPED ){
          $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
          foreach($associatedProducts as $associatedProduct){
            $productStock += $this->getSourceStockBySku($associatedProduct->getSku());
          }
        }
        if( $productType == self::PRODUCT_TYPE_BUNDLE ){
          $productsArray = array();
          $selectionCollection = $item->getTypeInstance(true)->getSelectionsCollection(
            $item->getTypeInstance(true)->getOptionsIds($item),
            $item
          );

          foreach ($selectionCollection as $proselection) {
            $selectionArray = array();
            $selectionArray['min_qty'] = $proselection->getSelectionQty();
            $selectionArray['stock'] = $this->stockStateInterface->getStockQty($proselection->getProductId(), $item->getStore()->getWebsiteId());
            $productsArray[$proselection->getOptionId()][$proselection->getSelectionId()] = $selectionArray;
          }

          $bundle_stock = 0;
          foreach ($productsArray as $bundle_item) {
            $bundle_option_min_stock = 0;
            foreach ($bundle_item as $bundle_option) {
              if ((integer)$bundle_option['min_qty'] <= $bundle_option['stock']) {
                $bundle_option_min_stock = ($bundle_option_min_stock == 0) ? $bundle_option['stock'] : $bundle_option_min_stock;
                $bundle_option_min_stock = ($bundle_option_min_stock < $bundle_option['stock']) ? $bundle_option['stock'] : $bundle_option_min_stock;
              }
            }
            $bundle_stock = ($bundle_stock == 0) ? $bundle_option_min_stock : $bundle_stock;
            $bundle_stock = ($bundle_stock < $bundle_option_min_stock) ? $bundle_option_min_stock : $bundle_stock;
          }

          $productStock = $bundle_stock;
        }
        return $productStock;
      });

    } catch (\Exception $e) {
      $this->clerk_logger->error('Getting Field Handlers Error', ['error' => $e->getMessage()]);
    }
  }

  /**
   * Get source stock from SKU
   * @param string|int $sku
   * @return int
   */
  protected function getSourceStockBySku($sku){
    $sourceItems = $this->itemSource->execute($sku);
    $stockTotal = 0;
    foreach($sourceItems as $sourceItem){
      $stockTotal += $sourceItem->getQuantity();
    }
    return $stockTotal;
  }

  /**
   * Get Product stock from interface
   */
  protected function getProductStockStateQty($product){
    $product_stock = $this->stockStateInterface->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
    if(isset($product_stock)){
      return $product_stock;
    } else {
      return 0;
    }
  }


  /**
   * Get Global Stock
   * @param string|int $sku
   * @return int
   */

  protected function getSaleableStockBySku($sku){
    $stockInfo = $this->getSalableQuantityDataBySku->execute($sku);
    $stockQuantity = 0;
    if(!empty($stockInfo)){
      foreach($stockInfo as $stockEntity){
        if(array_key_exists('qty', $stockEntity)){
          $stockQuantity += $stockEntity['qty'];
        }
      }
    }
    return $stockQuantity;
  }


  /**
   * Get Product price with contextual taxes
   */

  protected function getProductTaxPrice($product, $price, $withTax=true){
    $store = $this->getStoreFromContext();
    return $this->taxHelper->getTaxPrice($product, $price, $withTax, null, null, null, $store, null, true);
  }

  /**
   * Format Price to 2 decimals
   * @param float|int $price
   * @return float|int $price
   */
  protected function formatPrice($price){
    return (float) number_format( (float) $price, 2, ".", "" );
  }

  /**
   * Format Image Path Valid
   * @param string $imagePath
   * @return string $imagePath
   */
  protected function fixImagePath($imagePath){
    if(strpos($imagePath, 'catalog/product/') > -1){
      return $imagePath;
    } else {
      return str_replace('catalog/product', 'catalog/product/', $imagePath);
    }
  }

  protected function getStoreFromContext(){
    $requestParams = $this->requestInterface->getParams();
    if(array_key_exists('scope_id', $requestParams)){
      return $this->storeManager->getStore($requestParams['scope_id']);
    } else {
      return $this->storeManager->getStore();
    }
  }

  protected function getStoreIdFromContext(){
    $requestParams = $this->requestInterface->getParams();
    if (array_key_exists('scope_id', $requestParams)){
      return $requestParams['scope_id'];
    } else {
      return $this->storeManager->getStore()->getId();
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
        'stock',
        'product_type',
        'tier_price_values',
        'tier_price_quantities',
        'child_stocks',
        'child_images',
        'child_prices',
        'child_list_prices',
        'tax_rate'
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

