<?php

namespace Clerk\Clerk\Model\Adapter;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

abstract class AbstractAdapter
{
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
        ClerkLogger $clerkLogger
    )
    {
        $this->clerk_logger = $clerkLogger;
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
    public function getInfoForItem($resourceItem,$scope, $scopeid)
    {
        try {

            $info = [];

            $this->setFields([], $scope, $scopeid);

            foreach ($this->getFields() as $field) {
                if (isset($resourceItem[$field])) {
                    $info[$this->getFieldName($field)] = $this->getAttributeValue($resourceItem, $field);
                }

                //21-10-2021 KKY Additional Fields for Configurable and grouped Products - start
                $additionalFields = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS, $scope, $scopeid);
                $customFields = is_string($additionalFields) ? str_replace(' ','' ,explode(',', $additionalFields)) : array();

                if(in_array($field, $customFields)){

                    if ($resourceItem->getTypeId() === Configurable::TYPE_CODE){

                        $configurablelist=[];
                        $entityField = 'entity_'.$field;
                        $usedProducts = $resourceItem->getTypeInstance()->getUsedProducts($resourceItem);
                        if (!empty($usedProducts)) {
                            foreach ($usedProducts as $simple) {
                                if (isset($simple[$field])) {
                                    $configurablelist[] = $this->getAttributeValue($simple, $field);
                                }elseif(isset($simple[$entityField])){
                                    $configurablelist[] = $this->getAttributeValue($simple, $entityField);
                                }
                            }
                        }
                        if(!empty($configurablelist)){
                            $info["child_".$this->getFieldName($field)."s"] = array_values(array_unique($configurablelist));
                        }

                    }

                    if ($resourceItem->getTypeId() === "grouped"){

                        $groupedList=[];
                        $entityField = 'entity_'.$field;
                        $associatedProducts = $resourceItem->getTypeInstance()->getAssociatedProducts($resourceItem);
                        //find simple products
                        if (!empty($associatedProducts)) {
                            foreach ($associatedProducts as $associatedProduct) {

                                if (isset($associatedProduct[$field])) {
                                    $groupedList[] = $this->getAttributeValue($associatedProduct, $field);
                                }elseif(isset($associatedProduct[$entityField])){
                                    $groupedList[] = $this->getAttributeValue($associatedProduct, $entityField);
                                }
                            }
                        }

                        if(!empty($groupedList)){
                            $info["child_".$this->getFieldName($field)."s"] = array_values(array_unique($groupedList));
                        }

                    }
                }
                //21-10-2021 KKY Additional Fields for Configurable and grouped Products - end

                if (isset($this->fieldHandlers[$field])) {
                    if (in_array($this->getFieldName($field), ['price','list_price'])) {
                            $price = str_replace(',','',$this->fieldHandlers[$field]($resourceItem));
                            $info[$this->getFieldName($field)] = (float)$price;
                    }
                    else {
                        $info[$this->getFieldName($field)] = $this->fieldHandlers[$field]($resourceItem);
                    }
                }

                if (array_key_exists($this->getFieldName($field),$info) != true) {
                    $info[$this->getFieldName($field)] = "";
                }
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
     * Get default fields
     * @return array
     */
    abstract protected function getDefaultFields($scope, $scopeid);
}
