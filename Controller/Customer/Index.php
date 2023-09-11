<?php

namespace Clerk\Clerk\Controller\Customer;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Newsletter\Model\SubscriberFactory as SubscriberFactory;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Framework\App\ProductMetadataInterface;

class Index extends AbstractAction
{

    /**
     * @var SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var SubscriberCollectionFactory
     */
    protected $_subscriberCollectionFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * @var CustomerMetadataInterface
     */
    protected $_customerMetadata;

    /**
     * @var ProductMetadataInterface
     */
    protected $_product_metadata;

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
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $customerCollectionFactory,
        LoggerInterface $logger,
        ModuleList $moduleList,
        ClerkLogger $clerk_logger,
        CustomerMetadataInterface $customerMetadata,
        ProductMetadataInterface $product_metadata,
        RequestApi $request_api,
        SubscriberFactory $subscriberFactory,
        SubscriberCollectionFactory $subscriberCollectionFactory
        )
    {
        $this->collectionFactory = $customerCollectionFactory;
        $this->clerk_logger = $clerk_logger;
        $this->_customerMetadata = $customerMetadata;
        $this->_storeManager = $storeManager;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_subscriberCollectionFactory = $subscriberCollectionFactory;

        parent::__construct(
            $context,
            $storeManager,
            $scopeConfig,
            $logger,
            $moduleList,
            $clerk_logger,
            $product_metadata,
            $request_api
        );
    }

    public function execute()
    {
        try {

            if ($this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_ENABLED, $this->scope, $this->scopeid)) {

                $Customers = [];
                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-Type', 'application/json', true);

                if (!empty($this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES, $this->scope, $this->scopeid))) {

                    $Fields = explode(',', str_replace(' ', '', $this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES, $this->scope, $this->scopeid)));

                } else {

                    $Fields = [];

                }

                $response = $this->getCustomerCollection($this->page, $this->limit, $this->scopeid);

                $subscriberInstance = $this->_subscriberFactory->create();

                foreach ($response->getData() as $customer) {

                    $_customer = array();
                    $_customer['id'] = $customer['entity_id'];
                    $_customer['name'] = $customer['firstname'] . " " . (!is_null($customer['middlename']) ? $customer['middlename'] . " " : "") . $customer['lastname'];
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

                    if($this->scopeConfig->getValue(Config::XML_PATH_SUBSCRIBER_SYNCHRONIZATION_ENABLED, $this->scope, $this->scopeid)) {
                        $sub_state = $subscriberInstance->loadByEmail($customer['email']);
                        if($sub_state->getId()) {
                            $_customer['subscribed'] = (bool) $sub_state->getSubscriberStatus();
                        } else {
                            $_customer['subscribed'] = false;
                        }
                    }

                    $Customers[] = $_customer;
                }

                if($this->scopeConfig->getValue(Config::XML_PATH_SUBSCRIBER_SYNCHRONIZATION_ENABLED, $this->scope, $this->scopeid)) {

                    $subscribersOnlyResponse = $this->getSubscriberCollection($this->page, $this->limit, $this->scopeid);

                    foreach ($subscribersOnlyResponse->getData() as $subscriber) {
                        if (isset($subscriber['subscriber_id'])) {
                            $_sub = array();
                            $_sub['id'] = 'SUB' . $subscriber['subscriber_id'];
                            $_sub['email'] = $subscriber['subscriber_email'];
                            $_sub['subscribed'] = (bool) $subscriber['subscriber_status'];
                            $_sub['name'] = "";
                            $_sub['firstname'] = "";
                            $Customers[] = $_sub;
                        }
                    }
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

    public function getCustomerCollection($page, $limit, $storeid)
    {
        $store = $this->_storeManager->getStore($storeid);
        $customerCollection = $this->collectionFactory->create();
        $customerCollection->setOrder('title', 'ASC');
        $customerCollection->addFilter('store_id', $store->getId());
        $customerCollection->setPageSize($limit);
        $customerCollection->setCurPage($page);
        return $customerCollection;
    }

    public function getSubscriberCollection($page, $limit, $storeid)
    {
        $subscriberCollection = $this->_subscriberCollectionFactory->create();
        $subscriberCollection->addFilter('store_id', $storeid);
        $subscriberCollection->addFilter('customer_id', 0);
        $subscriberCollection->setPageSize($limit);
        $subscriberCollection->setCurPage($page);
        return $subscriberCollection;
    }

    public function getCustomerGender($GenderCode)
    {
        return $this->_customerMetadata->getAttributeMetadata('gender')->getOptions()[$GenderCode]->getLabel();
    }
}
