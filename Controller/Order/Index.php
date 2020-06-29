<?php

namespace Clerk\Clerk\Controller\Order;

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

class Index extends AbstractAction
{
    /**
     * @var
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

    protected $moduleList;

    /**
     * Order controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $orderCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ModuleList $moduleList,
        ClerkLogger $ClerkLogger
    )
    {
        $this->collectionFactory = $orderCollectionFactory;
        $this->clerk_logger = $ClerkLogger;
        $this->moduleList = $moduleList;
        $this->addFieldHandlers();

        parent::__construct($context, $storeManager, $scopeConfig, $logger, $moduleList, $ClerkLogger);
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
                if ($this->scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS, ScopeInterface::SCOPE_STORE)) {
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
                        'quantity' => (int)$productItem->getQtyOrdered(),
                        'price' => (float)$productItem->getPrice(),
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
                ScopeInterface::SCOPE_STORE
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
