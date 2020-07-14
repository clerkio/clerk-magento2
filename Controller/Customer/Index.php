<?php

namespace Clerk\Clerk\Controller\Customer;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerMetadataInterface;

class Index extends AbstractAction
{

    protected $collectionFactory;
    protected $clerk_logger;
    protected $_customerMetadata;

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
    public function __construct(Context $context, StoreManagerInterface $storeManager, ScopeConfigInterface $scopeConfig, CollectionFactory $customerCollectionFactory, LoggerInterface $logger,  ModuleList $moduleList, ClerkLogger $ClerkLogger, CustomerMetadataInterface $customerMetadata)
    {
        $this->collectionFactory = $customerCollectionFactory;
        $this->clerk_logger = $ClerkLogger;
        $this->_customerMetadata = $customerMetadata;

        parent::__construct($context, $storeManager, $scopeConfig, $logger, $moduleList, $ClerkLogger);
    }

    public function execute()
    {
        try {

            if ($this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_ENABLED, ScopeInterface::SCOPE_STORE)) {

                $Customers = [];
                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-Type', 'application/json', true);
                if (!empty($this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES, ScopeInterface::SCOPE_STORE))) {

                    $Fields = explode(',',str_replace(' ','', $this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES, ScopeInterface::SCOPE_STORE)));

                } else {

                    $Fields = [];

                }

                $response = $this->getCustomerCollection();

                foreach ($response->getData() as $customer) {

                    $_customer = [];
                    $_customer['id'] = $customer['entity_id'];
                    $_customer['name'] = $customer['firstname'] . " " . ($customer['middlename'] ? $customer['middlename'] . " " : "") . $customer['lastname'];
                    $_customer['email'] = $customer['email'];

                    foreach ($Fields as $Field) {
                        if (isset($customer[$Field])) {
                            if ($Field == "gender") {

                                $_customer[$Field] = $this->getCustomerGender($customer[$Field]);

                            } else {

                                $_customer[$Field] = $customer[$Field];

                            }

                        }
                    }

                    $Customers[] = $_customer;
                }

                if ($this->debug) {
                    $this->getResponse()->setBody(json_encode($Customers, JSON_PRETTY_PRINT));
                } else {
                    $this->getResponse()->setBody(json_encode($Customers));
                }
            } else {

                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-Type', 'application/json', true);

                $this->getResponse()->setBody(json_encode([]));

            }

        } catch (\Exception $e) {

            $this->clerk_logger->error('Customer execute ERROR', ['error' => $e->getMessage()]);

        }

    }

    public function getCustomerCollection()
    {
        return $this->collectionFactory->create();
    }

    public function getCustomerGender($GenderCode)
    {
        return $this->_customerMetadata->getAttributeMetadata('gender')->getOptions()[$GenderCode]->getLabel();
    }

}
