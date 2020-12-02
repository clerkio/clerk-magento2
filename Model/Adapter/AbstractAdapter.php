<?php

namespace Clerk\Clerk\Model\Adapter;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

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
     * @return array
     */
    public function getResponse($fields, $page, $limit, $orderBy, $order)
    {
        try {
            
            $this->setFields($fields);

            $collection = $this->prepareCollection($page, $limit, $orderBy, $order);

            $response = [];

            if ($page <= $collection->getLastPageNumber()) {
                //Build response
                foreach ($collection as $resourceItem) {
                    $item = $this->getInfoForItem($resourceItem);

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
    abstract protected function prepareCollection($page, $limit, $orderBy, $order);

    /**
     * Get information for single resource item
     *
     * @param $fields
     * @param $resourceItem
     * @return array
     */
    public function getInfoForItem($resourceItem)
    {
        try {
            
            $info = [];

            $this->setFields([]);

            foreach ($this->getFields() as $field) {
                if (isset($resourceItem[$field])) {
                    $info[$this->getFieldName($field)] = $this->getAttributeValue($resourceItem, $field);
                }

                if (isset($this->fieldHandlers[$field])) {
                    if (in_array($this->getFieldName($field), ['price','list_price'])) {
                        $info[$this->getFieldName($field)] = (float)$this->fieldHandlers[$field]($resourceItem);
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
    public function setFields($fields)
    {
        $this->fields = array_merge(['entity_id'], $this->getDefaultFields(), (array)$fields);
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
    abstract protected function getDefaultFields();
}