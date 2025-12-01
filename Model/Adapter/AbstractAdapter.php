<?php

namespace Clerk\Clerk\Model\Adapter;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

abstract class AbstractAdapter
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
   * @var ScopeConfigInterface
   */
  protected $scopeConfig;

  /**
   * @var
   */
  protected $clerk_logger;

  /**
   * @var ManagerInterface
   */
  protected $eventManager;

  /**
   * @var StoreManagerInterface
   */
  protected $storeManager;

  /**
   * @var mixed
   */
  protected $collectionFactory;

  /**
   * @var array
   */
  protected $fieldMap;

  /**
   * @var array
   */
  protected $fields = [];

  /**
   * @var array
   */
  protected $fieldHandlers = [];

  /**
   * AbstractAdapter constructor.
   *
   * @param ScopeConfigInterface $scopeConfig
   * @param ManagerInterface $eventManager
   * @param StoreManagerInterface $storeManager
   * @param mixed $collectionFactory
   */
  public function __construct(
    ScopeConfigInterface $scopeConfig,
    ManagerInterface $eventManager,
    StoreManagerInterface $storeManager,
    CollectionFactory $collectionFactory,
    ClerkLogger $clerk_logger
  ) {
    $this->clerk_logger = $clerk_logger;
    $this->scopeConfig = $scopeConfig;
    $this->eventManager = $eventManager;
    $this->storeManager = $storeManager;
    $this->collectionFactory = $collectionFactory;
    $this->addFieldHandlers();
  }

  /**
   * Add default fieldhandlers
   */
  abstract protected function addFieldHandlers();

  /**
   * @param $fields
   * @param $page
   * @param $limit
   * @param $orderBy
   * @param $order
   * @param $scope
   * @param $scopeid
   * @return array
   */
  public function getResponse($fields, $page, $limit, $orderBy, $order, $scope, $scopeid)
  {
    try {

      if ($this->storeManager->isSingleStoreMode()) {
        $scope = 'store';
        $scopeid = $this->storeManager->getDefaultStoreView()->getId();
      }

      $this->setFields($fields, $scope, $scopeid);

      $collection = $this->prepareCollection($page, $limit, $orderBy, $order, $scope, $scopeid);

      $response = [];

      if ($page <= $collection->getLastPageNumber()) {
        //Build response
        foreach ($collection as $resourceItem) {
          $item = $this->getInfoForItem($resourceItem, $scope, $scopeid);

          $response[] = $item;
        }
      }

      return $response;

    } catch (\Exception $e) {

      $this->clerk_logger->error('Getting Response ERROR', ['error' => $e->getMessage()]);

    }
  }

  /**
   * @return mixed
   */
  abstract protected function prepareCollection($page, $limit, $orderBy, $order, $scope, $scopeid);

  /**
   * Get information for single resource item
   *
   * @param $fields
   * @param $resourceItem
   * @return array
   */
  public function getInfoForItem($resourceItem, $scope, $scopeid)
  {
    try {

      $info = array();
      $additionalFields = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS, $scope, $scopeid);
      $heavyAttributeQuery = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS_HEAVY_QUERY, $scope, $scopeid);
      $customFields = is_string($additionalFields) ? str_replace(' ', '', explode(',', $additionalFields)) : [];
      $resourceItemTypeId = $resourceItem->getTypeId();
      
      // Fix: Reset type instance to clear cached child product data
      // Magento's type instance caches getUsedProducts() and getAssociatedProducts() results
      // which causes child data (IDs, images, prices) to be shared between different products
      // when the same adapter instance processes multiple products sequentially (e.g., in observers)
      $resourceItem->setTypeInstance(null);
      $resourceItemTypeInstance = $resourceItem->getTypeInstance();

      $this->setFields($customFields, $scope, $scopeid);

      foreach ($this->getFields() as $field) {
        if (isset($this->fieldHandlers[$field])) {
          $info[$this->getFieldName($field)] = $this->fieldHandlers[$field]($resourceItem);
        }

        if (isset($resourceItem[$field]) && !array_key_exists($field, $info)) {
          $attributeValue = $this->getAttributeValue($resourceItem, $field);
          if(!isset($attributeValue) && $heavyAttributeQuery) {
            $attributeValue = $this->getAttributeValueHeavy($resourceItem, $field);
          }
          $info[$this->getFieldName($field)] = $attributeValue;
        }



        if ($resourceItemTypeId === self::PRODUCT_TYPE_CONFIGURABLE) {
          $usedProductsAttributeValues = array();
          $entityField = 'entity_'.$field;
          $usedProducts = $resourceItemTypeInstance->getUsedProducts($resourceItem);
          if ( ! empty($usedProducts) ){
            foreach ($usedProducts as $usedProduct) {
              if (isset($usedProduct[$field])) {
                $usedProductsAttributeValues[] = $this->getAttributeValue($usedProduct, $field);
              } elseif (isset($usedProduct[$entityField])) {
                $usedProductsAttributeValues[] = $this->getAttributeValue($usedProduct, $entityField);
              }
              if(empty($usedProductsAttributeValues) && $heavyAttributeQuery) {
                $attributeValue = $this->getAttributeValueHeavy($usedProduct, $field);
                if(isset($attributeValue)){
                  $usedProductsAttributeValues[] = $attributeValue;
                }
              }
            }
          }
          if ( ! empty($usedProductsAttributeValues) && !array_key_exists('child_'.$this->getFieldName($field).'s', $info) ) {
            $usedProductsAttributeValues = is_array($usedProductsAttributeValues) ? $this->flattenArray($usedProductsAttributeValues) : $usedProductsAttributeValues;
            $info["child_".$this->getFieldName($field)."s"] = $usedProductsAttributeValues;
          }
        }

        if ($resourceItemTypeId === self::PRODUCT_TYPE_GROUPED) {
          $associatedProductsAttributeValues = array();
          $entityField = 'entity_'.$field;
          $associatedProducts = $resourceItemTypeInstance->getAssociatedProducts($resourceItem);
          if ( ! empty($associatedProducts) ) {
            foreach ($associatedProducts as $associatedProduct) {
              if (isset($associatedProduct[$field])) {
                $associatedProductsAttributeValues[] = $this->getAttributeValue($associatedProduct, $field);
              } elseif (isset($associatedProduct[$entityField])) {
                $associatedProductsAttributeValues[] = $this->getAttributeValue($associatedProduct, $entityField);
              }
              if(empty($associatedProductsAttributeValues) && $heavyAttributeQuery) {
                $attributeValue = $this->getAttributeValueHeavy($associatedProduct, $field);
                if(isset($attributeValue)){
                  $associatedProductsAttributeValues[] = $attributeValue;
                }

              }
            }
          }

          if ( ! empty($associatedProductsAttributeValues) && !array_key_exists('child_'.$this->getFieldName($field).'s', $info)) {
            $associatedProductsAttributeValues = is_array($associatedProductsAttributeValues) ? $this->flattenArray($associatedProductsAttributeValues) : $associatedProductsAttributeValues;
            $info["child_".$this->getFieldName($field)."s"] = $associatedProductsAttributeValues;
          }

        }
      }

      if(isset($info['price']) && isset($info['list_price'])){
        $info['on_sale'] = (bool) ($info['price'] < $info['list_price']);
      }

      // Fix for bundle products not reliably having implicit tax.
      if(isset($info['tax_rate']) && $info['product_type'] == self::PRODUCT_TYPE_BUNDLE){
          if($info['price'] === $info['price_excl_tax']){
            $info['price_excl_tax'] = $info['price'] / (1 + ($info['tax_rate'] / 100) );
          }
          if($info['list_price'] === $info['list_price_excl_tax']){
            $info['list_price_excl_tax'] = $info['list_price'] / (1 + ($info['tax_rate'] / 100) );
          }
      }

      // Fix for including a list of Bundle Products child skus.
      if($resourceItemTypeId == self::PRODUCT_TYPE_BUNDLE){
        $bundle_skus = [];
        $selections = $resourceItem->getTypeInstance(true)->getSelectionsCollection($resourceItem->getTypeInstance(true)->getOptionsIds($resourceItem), $resourceItem);
        if( !empty($selections) ){
          foreach($selections as $selection){
            if( is_object($selection) ){
              $bundle_skus[] = $selection->getSku();
            }
          }
        }
        $info['bundle_skus'] = $bundle_skus;
      }

      return $info;
    } catch (\Exception $e) {

      $this->clerk_logger->error('Getting Response ERROR', ['error' => $e->getMessage()]);

    }
  }

  /**
   * Get list of fields
   *
   * @return array
   */
  public function getFields()
  {
    return $this->fields;
  }

  /**
   * Set fields to get
   *
   * @param $fields
   */
  public function setFields($fields, $scope, $scopeid)
  {
    $this->fields = array_merge(['entity_id'], $this->getDefaultFields($scope, $scopeid), (array)$fields);
  }

  /**
   * Get mapped field name
   *
   * @param $field
   * @return mixed
   */
  protected function getFieldName($field)
  {
    if (isset($this->fieldMap[$field])) {
      return $this->fieldMap[$field];
    }

    return $field;
  }

  /**
   * Get attribute value
   *
   * @param $resourceItem
   * @param $field
   * @return mixed
   */
  protected function getAttributeValue($resourceItem, $field)
  {
    return $resourceItem[$field];
  }

  /**
   * Add field to get
   *
   * @param $field
   */
  public function addField($field)
  {
    $this->fields[] = $field;
  }

  /**
   * Add fieldhandler
   *
   * @param $field
   * @param callable $handler
   */
  public function addFieldHandler($field, callable $handler)
  {
    $this->fieldHandlers[$field] = $handler;
  }

  /**
   * Flatten array
   *
   * @param array $array
   * @return array $array
   */
  public function flattenArray($array)
  {
    $return = [];
    array_walk_recursive($array, function ($a) use (&$return) {
      $return[] = $a;
    });
    return $return;
  }

  /**
   * Get attribute value for product by simulating resource
   *
   * @param $resourceItem
   * @param $field
   * @return mixed
   */
  public function getAttributeValueHeavy($resourceItem, $field)
  {
    try {

      $attributeResource = $resourceItem->getResource();

      if(in_array($resourceItem->getTypeId(), self::PRODUCT_TYPES)){
        $attributeResource->load($resourceItem, $resourceItem->getId(), [$field]);

        $customAttribute = $resourceItem->getCustomAttribute($field);
        if($customAttribute){
          return $customAttribute->getValue();
        }
      }

    } catch (\Exception $e) {

      $this->clerk_logger->error('Getting Attribute Value Error', ['error' => $e->getMessage()]);

    }
  }

  /**
   * Get default fields
   *
   * @param string $scope
   * @param int|string $scopeid
   * @return array
   */
  abstract protected function getDefaultFields($scope, $scopeid);
}
