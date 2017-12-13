<?php

namespace Clerk\Clerk\Controller\Order;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
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
        LoggerInterface $logger
    )
    {
        $this->collectionFactory = $orderCollectionFactory;

        $this->addFieldHandlers();

        parent::__construct($context, $scopeConfig, $logger);
    }

    /**
     * Execute request
     */
    public function execute()
    {
        $disabled = $this->scopeConfig->isSetFlag(
            \Clerk\Clerk\Model\Config::XML_PATH_PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION,
            ScopeInterface::SCOPE_STORE
        );

        if ($disabled) {
            $this->getResponse()
                 ->setHttpResponseCode(200)
                 ->setHeader('Content-Type', 'application/json', true)
                 ->setBody(json_encode([]));
            return;
        }

        parent::execute();
    }

    /**
     * Add field handlers
     */
    protected function addFieldHandlers()
    {
        //Add time fieldhandler
        $this->addFieldHandler('time', function($item) {
            return strtotime($item->getCreatedAt());
        });

        //Add email fieldhandler
        $this->addFieldHandler('email', function($item) {
            return $item->getCustomerEmail();
        });

        //Add customer fieldhandler
        $this->addFieldHandler('customer', function($item) {
            return $item->getCustomerId();
        });

        //Add products fieldhandler
        $this->addFieldHandler('products', function($item) {
            $products = [];
            foreach ($item->getAllVisibleItems() as $productItem) {
                $products[] = [
                    'id' => $productItem->getId(),
                    'quantity' => (int) $productItem->getQtyOrdered(),
                    'price' => $productItem->getPrice(),
                ];
            }

            return $products;
        });
    }

    /**
     * Parse request arguments
     */
    protected function getArguments(RequestInterface $request)
    {
        parent::getArguments($request);

        //Use increment id instead of entity_id
        $this->fields = str_replace('entity_id', 'increment_id', $this->fields);
    }
}