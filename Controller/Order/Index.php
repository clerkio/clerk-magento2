<?php

namespace Clerk\Clerk\Controller\Order;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Webapi\Rest\Request as RequestApi;

class Index extends AbstractAction
{
    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * @var array
     */
    protected $fieldMap = [
        'increment_id' => 'id',
    ];

    /**
     * @var string
     */
    protected $eventPrefix = 'clerk_order';

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * Order controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $orderCollectionFactory
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     * @param Api $api
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $orderCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ModuleList $moduleList,
        ClerkLogger $clerk_logger,
        ProductMetadataInterface $product_metadata,
        RequestApi $request_api,
        Api $api
    ) {
        $this->collectionFactory = $orderCollectionFactory;
        $this->clerk_logger = $clerk_logger;
        $this->moduleList = $moduleList;
        $this->addFieldHandlers();

        parent::__construct(
            $context,
            $storeManager,
            $scopeConfig,
            $logger,
            $moduleList,
            $clerk_logger,
            $product_metadata,
            $request_api,
            $api
        );
    }

    /**
     * Add field handlers
     */
    protected function addFieldHandlers()
    {

        try {

            //Add time fieldhandler
            $this->addFieldHandler('time', function ($item) {
                return strtotime($item->getCreatedAt());
            });

            //Add email fieldhandler
            $this->addFieldHandler('email', function ($item) {
                if ($this->scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS, $this->scope, $this->scopeid)) {
                    return $item->getCustomerEmail();
                }

                return null;
            });

            //Add customer fieldhandler
            $this->addFieldHandler('customer', function ($item) {
                return $item->getCustomerId();
            });

            //Add products fieldhandler
            $this->addFieldHandler('products', function ($item) {
                $products = [];
                foreach ($item->getAllVisibleItems() as $productItem) {
                    $products[] = [
                        'id' => $productItem->getProductId(),
                        'quantity' => (int) $productItem->getQtyOrdered(),
                        'price' => (float) $productItem->getPrice(),
                    ];
                }
                return $products;
            });

        } catch (\Exception $e) {

            $this->clerk_logger->error('Order addFieldHandlers ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Execute request
     */
    public function execute()
    {
        try {

            $disabled = $this->scopeConfig->isSetFlag(
                Config::XML_PATH_PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION,
                $this->scope,
                $this->scopeid
            );

            if ($disabled) {
                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-Type', 'application/json', true)
                    ->setBody(json_encode([]));

                $this->clerk_logger->log('Order Sync Disabled', ['response' => '']);

                return;
            }

            parent::execute();

        } catch (\Exception $e) {

            $this->clerk_logger->error('Order execute ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Prepare collection with multi-store support
     *
     * @return object|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function prepareCollection()
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToSelect('*');

            // Check if we should import orders from all stores
            $importFromAllStores = $this->scopeConfig->isSetFlag(
                Config::XML_PATH_PRODUCT_SYNCHRONIZATION_IMPORT_ORDERS_FROM_ALL_STORES,
                $this->scope,
                $this->scopeid
            );

            if ($this->start_date) {
                $collection->setPageSize($this->limit)
                    ->setCurPage($this->page)
                    ->addAttributeToFilter('created_at', ['from' => $this->start_date, 'to' => $this->end_date])
                    ->addOrder($this->orderBy, $this->order);
            } else {
                $collection->setPageSize($this->limit)
                    ->setCurPage($this->page)
                    ->addOrder($this->orderBy, $this->order);
            }

            // Log the collection strategy being used
            if ($importFromAllStores) {
                $this->clerk_logger->log('Order Collection: Importing from ALL stores', [
                    'scope' => $this->scope,
                    'scope_id' => $this->scopeid,
                    'import_all_stores' => true
                ]);
            } else {
                $this->clerk_logger->log('Order Collection: Importing from CURRENT store only', [
                    'scope' => $this->scope,
                    'scope_id' => $this->scopeid,
                    'import_all_stores' => false
                ]);
            }

            return $collection;

        } catch (\Exception $e) {
            $this->clerk_logger->error('Order prepareCollection ERROR', ['error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Determine if store filter should be applied to order collection
     * Checks configuration to decide between single-store or multi-store import
     *
     * @return bool
     */
    protected function shouldApplyStoreFilter()
    {
        try {
            // Check if we should import orders from all stores
            $importFromAllStores = $this->scopeConfig->isSetFlag(
                Config::XML_PATH_PRODUCT_SYNCHRONIZATION_IMPORT_ORDERS_FROM_ALL_STORES,
                $this->scope,
                $this->scopeid
            );

            // If importing from all stores, don't apply store filter
            if ($importFromAllStores) {
                $this->clerk_logger->log('Order Store Filter: DISABLED (importing from all stores)', [
                    'scope' => $this->scope,
                    'scope_id' => $this->scopeid
                ]);
                return false;
            }

            // Default behavior: apply store filter
            $this->clerk_logger->log('Order Store Filter: ENABLED (importing from current store only)', [
                'scope' => $this->scope,
                'scope_id' => $this->scopeid
            ]);
            return true;

        } catch (\Exception $e) {
            $this->clerk_logger->error('Order shouldApplyStoreFilter ERROR', ['error' => $e->getMessage()]);
            // On error, default to applying store filter for safety
            return true;
        }
    }

    /**
     * Parse request arguments
     */
    protected function getArguments(RequestInterface $request)
    {
        try {
            parent::getArguments($request);

            //Use increment id instead of entity_id
            $this->fields = str_replace('entity_id', 'increment_id', $this->fields);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Order getArguments ERROR', ['error' => $e->getMessage()]);

        }
    }
}
