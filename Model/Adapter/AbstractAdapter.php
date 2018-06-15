<?php

namespace Clerk\Clerk\Model\Adapter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractAdapter
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

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
     * @param $collectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        $collectionFactory
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;

        $this->addFieldHandlers();
    }

    /**
     * @return mixed
     */
    abstract protected function prepareCollection($page, $limit, $orderBy, $order);

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
        $this->setFields($fields);

        $collection = $this->prepareCollection($page, $limit, $orderBy, $order);

        $response = [];

        //Build response
        foreach ($collection as $resourceItem) {
            $item = $this->getInfoForItem($resourceItem);

            $response[] = $item;
        }

        return $response;
    }

    /**
     * Set fields to get
     *
     * @param $fields
     */
    public function setFields($fields)
    {
        $this->fields = array_merge(['entity_id'], (array)$fields);
    }

    /**
     * Get list of fields
     *
     * @return array
     */
    public function getFields()
    {
        if (empty($this->fields)) {
            return $this->getDefaultFields();
        }

        return $this->fields;
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
     * Add default fieldhandlers
     */
    abstract protected function addFieldHandlers();

    /**
     * Get default fields
     * @return array
     */
    abstract protected function getDefaultFields();

    /**
     * Get information for single resource item
     *
     * @param $fields
     * @param $resourceItem
     * @return array
     */
    public function getInfoForItem($resourceItem)
    {
        $info = [];

        foreach ($this->getFields() as $field) {
            if (isset($resourceItem[$field])) {
                $info[$this->getFieldName($field)] = $this->getAttributeValue($resourceItem, $field);
            }

            if (isset($this->fieldHandlers[$field])) {
                $info[$this->getFieldName($field)] = $this->fieldHandlers[$field]($resourceItem);
            }
        }

        return $info;
    }
}