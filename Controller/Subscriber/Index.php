<?php

namespace Clerk\Clerk\Controller\Subscriber;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Framework\App\ProductMetadataInterface;

class Index extends AbstractAction
{
    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * subscriber controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $suscriberCollectionFactory
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $suscriberCollectionFactory,
        LoggerInterface $logger,
        ModuleList $moduleList,
        ClerkLogger $clerk_logger,
        ProductMetadataInterface $product_metadata,
        RequestApi $request_api
        )
    {
        $this->collectionFactory = $suscriberCollectionFactory;
        $this->clerk_logger = $clerk_logger;
        $this->_storeManager = $storeManager;

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

                $Subscribers = [];
                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-Type', 'application/json', true);
                if (!empty($this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES, $this->scope, $this->scopeid))) {

                    $Fields = explode(',', str_replace(' ', '', $this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES, $this->scope, $this->scopeid)));

                } else {

                    $Fields = [];

                }

                    $response = $this->getSubscriberCollection($this->page, $this->limit, $this->scopeid);

                foreach ($response->getData() as $subscriber) {
                    $_subscriber = [];

                    $_subscriber['id'] = $subscriber['subscriber_id'];
                    $_subscriber['customerid'] = $subscriber['customer_id'];
                    $_subscriber['status'] = $subscriber['subscriber_status'];
                    $_subscriber['email'] = $subscriber['subscriber_email'];
                    $Subscribers[] = $_subscriber;
                }

                if ($this->debug) {
                    $this->getResponse()->setBody(json_encode($Subscribers, JSON_PRETTY_PRINT));
                } else {
                    $this->getResponse()->setBody(json_encode($Subscribers));
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

    public function getSubscriberCollection($page, $limit, $storeid)
    {
        $store = $this->_storeManager->getStore($storeid);
        $collection = $this->collectionFactory->create();
        $collection->addFilter('store_id', $store->getId());
        $collection->addStoreFilter($store);
        $collection->setPageSize($limit);
        $collection->setCurPage($page);
        return $collection;
    }
}
