<?php

namespace Clerk\Clerk\Model\Adapter;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractAdapter
{

    public const PRODUCT_TYPE_SIMPLE = 'simple';
    public const PRODUCT_TYPE_CONFIGURABLE = 'configurable';
    public const PRODUCT_TYPE_GROUPED = 'grouped';
    public const PRODUCT_TYPE_BUNDLE = 'bundle';
    public const PRODUCT_TYPES = [
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
     * @var ClerkLogger
     */
    protected $clerkLogger;

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
     * @param ClerkLogger $clerkLogger
     */
    public function __construct(
        ScopeConfigInterface  $scopeConfig,
        ManagerInterface      $eventManager,
        StoreManagerInterface $storeManager,
        CollectionFactory     $collectionFactory,
        ClerkLogger           $clerkLogger
    )
    {
        $this->clerkLogger = $clerkLogger;
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
     * Get request response
     *
     * @param array $fields
     * @param int|string $page
     * @param int|string $limit
     * @param int|string $orderBy
     * @param int|string $order
     * @param string $scope
     * @param int|string $scopeid
     * @return array|void
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
                foreach ($collection as $item) {
                    $response[] = $this->getInfoForItem($item, $scope, $scopeid);
                }
            }
            return $response;

        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Response ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Abstract prepare collection
     *
     * @param int|string $page
     * @param int|string $limit
     * @param int|string $orderBy
     * @param int|string $order
     * @param string $scope
     * @param int|string $scopeid
     * @return mixed
     */
    abstract protected function prepareCollection($page, $limit, $orderBy, $order, $scope, $scopeid);

    /**
     * Get information for single resource item
     *
     * @param object $resourceItem
     * @param string $scope
     * @param int|string $scopeid
     * @return array|void
     */
    public function getInfoForItem($resourceItem, $scope, $scopeid)
    {
        try {

            $info = [];
            $additional_fields =
                $this->scopeConfig->getValue(
                    Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS,
                    $scope,
                    $scopeid
                );
            $emulate_inactive_products =
                $this->scopeConfig->getValue(
                    Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS_HEAVY_QUERY,
                    $scope,
                    $scopeid
                );
            $custom_fields = is_string($additional_fields) ? array_map('trim', explode(',', $additional_fields)) : [];
            $resource_item_type_id = $resourceItem->getTypeId();
            $resource_item_type_instance = $resourceItem->getTypeInstance();

            $this->setFields($custom_fields, $scope, $scopeid);

            foreach ($this->getFields() as $field) {
                if (isset($this->fieldHandlers[$field])) {
                    $info[$this->getFieldName($field)] = $this->fieldHandlers[$field]($resourceItem);
                }

                if (isset($resourceItem[$field]) && !array_key_exists($field, $info)) {
                    $attribute_value = $this->getAttributeValue($resourceItem, $field);
                    if (!isset($attribute_value) && $emulate_inactive_products) {
                        $attribute_value = $this->getAttributeValueHeavy($resourceItem, $field);
                    }
                    $info[$this->getFieldName($field)] = $attribute_value;
                }

                if ($resource_item_type_id === self::PRODUCT_TYPE_CONFIGURABLE) {
                    $used_products = $resource_item_type_instance->getUsedProducts($resourceItem);
                    $info = $this->getChildAttributes($used_products, $info, $field, $emulate_inactive_products);
                }

                if ($resource_item_type_id === self::PRODUCT_TYPE_GROUPED) {
                    $associated_products = $resource_item_type_instance->getAssociatedProducts($resourceItem);
                    $info = $this->getChildAttributes($associated_products, $info, $field, $emulate_inactive_products);
                }
            }

            if (isset($info['price']) && isset($info['list_price'])) {
                $info['on_sale'] = $info['price'] < $info['list_price'];
            }

            // Fix for bundle products not reliably having implicit tax.
            $info = $this->fixForBundleProductsNotReliablyHavingImplicitTax($info);

            // Fix for including a list of Bundle Products child skus.
            if ($resource_item_type_id == self::PRODUCT_TYPE_BUNDLE) {
                $bundle_skus = [];
                $selections = $resourceItem->getTypeInstance(true)->getSelectionsCollection(
                    $resourceItem->getTypeInstance(true)->getOptionsIds($resourceItem),
                    $resourceItem
                );
                if (!empty($selections)) {
                    foreach ($selections as $selection) {
                        if (is_object($selection)) {
                            $bundle_skus[] = $selection->getSku();
                        }
                    }
                }
                $info['bundle_skus'] = $bundle_skus;
            }

            return $info;
        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Response ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get an array of fields
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
     * @param array $fields
     * @param string $scope
     * @param int|string $scopeid
     */
    public function setFields($fields, $scope, $scopeid)
    {
        $this->fields = array_merge(['entity_id'], $this->getDefaultFields($scope, $scopeid), (array)$fields);
    }

    /**
     * Get mapped field name
     *
     * @param string $field
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
     * @param object $resourceItem
     * @param string $field
     * @return mixed
     */
    protected function getAttributeValue($resourceItem, $field)
    {
        return $resourceItem[$field];
    }

    /**
     * Get attribute value for product by simulating resource
     *
     * @param object $resourceItem
     * @param string $field
     * @return mixed|void
     */
    public function getAttributeValueHeavy($resourceItem, $field)
    {
        try {

            $attribute_resource = $resourceItem->getResource();

            if (in_array($resourceItem->getTypeId(), self::PRODUCT_TYPES)) {
                $attribute_resource->load($resourceItem, $resourceItem->getId(), [$field]);

                $attribute = $resourceItem->getCustomAttribute($field);
                if ($attribute) {
                    return $attribute->getValue();
                }
            }

        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Attribute Value Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get child attributes
     *
     * @param array $related_products
     * @param array $export_data
     * @param string $field
     * @param bool $emulate_deactivated
     * @return array
     */
    public function getChildAttributes($related_products, $export_data, $field, $emulate_deactivated)
    {
        $child_attribute_values = [];
        $entity_field = 'entity_' . $field;
        if (empty($related_products)) {
            return $export_data;
        }
        foreach ($related_products as $related_product) {
            if (isset($related_product[$field])) {
                $child_attribute_values[] = $this->getAttributeValue($related_product, $field);
            } elseif (isset($related_product[$entity_field])) {
                $child_attribute_values[] = $this->getAttributeValue($related_product, $entity_field);
            }
            if (empty($child_attribute_values) && $emulate_deactivated) {
                $attribute_value = $this->getAttributeValueHeavy($related_product, $field);
                if (isset($attribute_value)) {
                    $child_attribute_values[] = $attribute_value;
                }
            }
        }
        $attribute_key = 'child_' . $this->getFieldName($field) . 's';
        if (!empty($child_attribute_values) && !array_key_exists($attribute_key, $export_data)) {
            $child_attribute_values = $this->flattenArray($child_attribute_values);
            $export_data[$attribute_key] = $child_attribute_values;

        }
        return $export_data;
    }

    /**
     * Flatten array
     *
     * @param array $array
     * @return array $array
     */
    public function flattenArray($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (!is_array($array)) {
            return $array;
        }
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }

    /**
     * @param array $info
     * @return array
     */
    public function fixForBundleProductsNotReliablyHavingImplicitTax(array $info): array
    {
        if (isset($info['tax_rate']) && $info['product_type'] == self::PRODUCT_TYPE_BUNDLE) {
            if ($info['price'] === $info['price_excl_tax']) {
                $info['price_excl_tax'] = $info['price'] / (1 + ($info['tax_rate'] / 100));
            }
            if ($info['list_price'] === $info['list_price_excl_tax']) {
                $info['list_price_excl_tax'] = $info['list_price'] / (1 + ($info['tax_rate'] / 100));
            }
        }
        return $info;
    }

    /**
     * Add field to get
     *
     * @param string $field
     */
    public function addField($field)
    {
        $this->fields[] = $field;
    }

    /**
     * Add fieldhandler
     *
     * @param string $field
     * @param callable $handler
     */
    public function addFieldHandler($field, callable $handler)
    {
        $this->fieldHandlers[$field] = $handler;
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
