<?php

namespace Clerk\Clerk\Controller\Customer;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    /**
     * @var array
     */
    protected $fieldMap = [
        'entity_id' => 'id',
    ];

    protected $moduleList;

    /**
     * @var string
     */
    protected $eventPrefix = 'clerk_customer';

    /**
     * Customer controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $customerCollectionFactory
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, CollectionFactory $customerCollectionFactory, LoggerInterface $logger,  ModuleList $moduleList, ClerkLogger $ClerkLogger)
    {
        $this->collectionFactory = $customerCollectionFactory;
        $this->moduleList = $moduleList;

        parent::__construct($context, $scopeConfig, $logger, $moduleList, $ClerkLogger);
    }
}