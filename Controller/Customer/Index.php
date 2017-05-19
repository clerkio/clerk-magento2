<?php

namespace Clerk\Clerk\Controller\Customer;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    protected $fieldMap = [
        'entity_id' => 'id',
    ];

    /**
     * Customer controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $customerCollectionFactory
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, CollectionFactory $customerCollectionFactory, LoggerInterface $logger)
    {
        $this->collectionFactory = $customerCollectionFactory;

        parent::__construct($context, $scopeConfig, $logger);
    }
}