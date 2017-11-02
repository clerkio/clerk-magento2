<?php

namespace Clerk\Clerk\Controller\Order;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    protected $fieldMap = [
        'entity_id' => 'id',
    ];

    protected $clerkEventPrefix = 'clerk_order';

    /**
     * Order controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $orderCollectionFactory
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, CollectionFactory $orderCollectionFactory, LoggerInterface $logger)
    {
        $this->collectionFactory = $orderCollectionFactory;

        $this->addFieldHandlers();

        parent::__construct($context, $scopeConfig, $logger);
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
}