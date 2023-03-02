<?php

namespace Clerk\Clerk\Controller;

use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Framework\App\ProductMetadataInterface;

abstract class AbstractAction extends Action
{
    /**
     * @var RequestApi
     */
    protected $_request_api;

    /**
     * @var
     */
    protected $clerk_logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var
     */
    protected $configWriter;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var array
     */
    protected $fieldHandlers = [];

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var
     */
    protected $start_date;

    /**
     * @var
     */
    protected $end_date;

    /**
     * @var string
     */
    protected $orderBy;

    /**
     * @var string
     */
    protected $order;

    /**
     * @var array
     */
    protected $fieldMap = [];

    /**
     * @var mixed
     */
    protected $collectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $eventPrefix = '';

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductMetadataInterface
     */
    protected $_product_metadata;

    /**
     * AbstractAction constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ModuleList $moduleList
     * @param ClerkLogger $clerk_logger
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ModuleList $moduleList,
        ClerkLogger $clerk_logger,
        ProductMetadataInterface $product_metadata,
        RequestApi $request_api
        )
    {
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->_storeManager = $storeManager;
        $this->clerk_logger = $clerk_logger;
        $this->_product_metadata = $product_metadata;
        $this->_request_api = $request_api;
        parent::__construct($context);
    }



    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {

        try {

            $version = $this->_product_metadata->getVersion();
            header('User-Agent: ClerkExtensionBot Magento 2/v' . $version . ' clerk/v' . $this->moduleList->getOne('Clerk_Clerk')['setup_version'] . ' PHP/v' . phpversion());

            $this->privateKey = $this->getRequestBodyParam('private_key');
            $this->publicKey = $this->getRequestBodyParam('key');

            //Validate supplied keys
            if (($this->verifyKeys() === -1 && $this->verifyWebsiteKeys() === -1 && $this->verifyDefaultKeys() === -1) || !$this->privateKey || !$this->publicKey) {
                $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
                $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);

                //Display error
                $this->getResponse()
                    ->setHttpResponseCode(403)
                    ->representJson(
                        json_encode([
                            'error' => [
                                'code' => 403,
                                'message' => __(' Invalid Authentification, please provide valid credentials.'),
                            ]
                        ])
                    );

                $this->clerk_logger->warn('Invalid keys supplied', ['response' => parent::dispatch($request)]);

                return parent::dispatch($request);
            }

            if ($this->verifyWebsiteKeys() !==-1) {
                $scopeID = $this->verifyWebsiteKeys();
                $request->setParams(['scope_id' => $scopeID]);
                $request->setParams(['scope' => 'website']);
            }

            if ($this->verifyKeys() !==-1) {
                $scopeID = $this->verifyKeys();
                $request->setParams(['scope_id' => $scopeID]);
                $request->setParams(['scope' => 'store']);

            }

            if ($this->_storeManager->isSingleStoreMode()) {
                $scopeID = $this->verifyDefaultKeys();
                $request->setParams(['scope_id' => $scopeID]);
                $request->setParams(['scope' => 'default']);
            }

            //Filter out request arguments
            $this->getArguments($request);
            return parent::dispatch($request);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Validating API Keys ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Verify public & private key
     *
     * @return bool
     */
    private function verifyDefaultKeys()
    {

        try {

            $privateKey = $this->getRequestBodyParam('private_key');
            $publicKey = $this->getRequestBodyParam('key');
            $scopeID = $this->_storeManager->getDefaultStoreView()->getId();
            if ($this->timingSafeEquals($this->getPrivateDefaultKey($scopeID), $privateKey) && $this->timingSafeEquals($this->getPublicDefaultKey($scopeID), $publicKey)) {
                return $scopeID;
            }

            return -1;

        } catch (\Exception $e) {

            $this->clerk_logger->error('verifyKeys ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Verify public & private key
     *
     * @return bool
     */
    private function verifyKeys()
    {

        try {

            $privateKey = $this->getRequestBodyParam('private_key');
            $publicKey = $this->getRequestBodyParam('key');
            $storeids = $this->getStores();
            foreach ($storeids as $scopeID) {
                if ($this->timingSafeEquals($this->getPrivateKey($scopeID), $privateKey) && $this->timingSafeEquals($this->getPublicKey($scopeID), $publicKey)) {
                    return $scopeID;
                }
            }

            return -1;

        } catch (\Exception $e) {

            $this->clerk_logger->error('verifyKeys ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Verify public & private key
     *
     * @return bool
     */
    private function verifyWebsiteKeys()
    {

        try {

            $privateKey = $this->getRequestBodyParam('private_key');
            $publicKey = $this->getRequestBodyParam('key');
            $websiteids = $this->getWebsites();
            foreach ($websiteids as $scopeID) {
                if ($this->timingSafeEquals($this->getPrivateWebsiteKey($scopeID), $privateKey) && $this->timingSafeEquals($this->getPublicWebsiteKey($scopeID), $publicKey)) {
                    return $scopeID;
                }
            }

            return -1;

        } catch (\Exception $e) {

            $this->clerk_logger->error('verifyKeys ERROR', ['error' => $e->getMessage()]);

        }
    }


    /**
     * Get public store key
     *
     * @return string
     */
    private function getPublicDefaultKey($scopeID)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PUBLIC_KEY,
                ScopeInterface::SCOPE_STORE,
                $scopeID
            );

        } catch (\Exception $e) {

            $this->clerk_logger->error('getPublicKey ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get private website key
     *
     * @return string
     */
    private function getPrivateDefaultKey($scopeID)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PRIVATE_KEY,
                ScopeInterface::SCOPE_STORE,
                $scopeID
            );

        } catch (\Exception $e) {

            $this->clerk_logger->error('getPrivateKey ERROR', ['error' => $e->getMessage()]);

        }
    }


    /**
     * Get private store key
     *
     * @return string
     */
    private function getPrivateKey($scopeID)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PRIVATE_KEY,
                ScopeInterface::SCOPE_STORES,
                $scopeID
            );

        } catch (\Exception $e) {

            $this->clerk_logger->error('getPrivateKey ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get private website key
     *
     * @return string
     */
    private function getPrivateWebsiteKey($scopeID)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PRIVATE_KEY,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeID
            );

        } catch (\Exception $e) {

            $this->clerk_logger->error('getPrivateKey ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get public store key
     *
     * @return string
     */
    private function getPublicKey($scopeID)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PUBLIC_KEY,
                ScopeInterface::SCOPE_STORES,
                $scopeID
            );

        } catch (\Exception $e) {

            $this->clerk_logger->error('getPublicKey ERROR', ['error' => $e->getMessage()]);

        }
    }


    /**
     * Get public website key
     *
     * @return string
     */
    private function getPublicWebsiteKey($scopeID)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PUBLIC_KEY,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeID
            );

        } catch (\Exception $e) {

            $this->clerk_logger->error('getPublicKey ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Timing safe key comparison
     *
     * @return boolean
     */
    private function timingSafeEquals($safe, $user)
    {
        $safeLen = strlen($safe);
        $userLen = strlen($user);

        if ($userLen != $safeLen) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $userLen; $i++) {
            $result |= (ord($safe[$i]) ^ ord($user[$i]));
        }

        // They are only identical strings if $result is exactly 0...
        return $result === 0;
    }

    /**
     * Parse request arguments
     */
    protected function getArguments(RequestInterface $request)
    {
        try {

            $this->debug = (bool)$request->getParam('debug', false);
            $this->start_date = date('Y-m-d', $request->getParam('start_date', strtotime('today - 200 years')));
            $this->end_date = date('Y-m-d', $request->getParam('end_date', strtotime('today + 1 day')));
            $this->limit = (int)$request->getParam('limit', 0);
            $this->page = (int)$request->getParam('page', 0);
            $this->orderBy = $request->getParam('orderby', 'entity_id');
            $this->scope = $request->getParam('scope');
            $this->scopeid = $request->getParam('scope_id');

            if ($request->getParam('order') === 'desc') {
                $this->order = \Magento\Framework\Data\Collection::SORT_ORDER_DESC;
            } else {
                $this->order = \Magento\Framework\Data\Collection::SORT_ORDER_ASC;
            }

            /**
             * Explode fields on , and filter out "empty" entries
             */
            $fields = $request->getParam('fields');
            if ($fields) {
                $this->fields = array_filter(explode(',', $fields), 'strlen');
            } else {
                $this->fields = $this->getDefaultFields();
            }
            $this->fields = array_merge(['entity_id'], $this->fields);

            foreach ($this->fields as $key => $field) {

                $this->fields[$key] = str_replace(' ', '', $field);

            }

        } catch (\Exception $e) {

            $this->clerk_logger->error('getArguments ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get default fields
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        return [];
    }

    /**
     * Execute request
     */
    public function execute()
    {
        try {

            $collection = $this->prepareCollection()->addFieldToFilter('store_id', $this->scopeid);

            $this->_eventManager->dispatch($this->eventPrefix . '_get_collection_after', [
                'controller' => $this,
                'collection' => $collection
            ]);

            $response = [];

            if ($this->page <= $collection->getLastPageNumber()) {
                //Build response
                foreach ($collection as $resourceItem) {
                    $item = [];

                    foreach ($this->fields as $field) {
                        if (isset($resourceItem[$field])) {
                            $item[$this->getFieldName($field)] = $this->getAttributeValue($resourceItem, $field);
                        }

                        if (isset($this->fieldHandlers[$field])) {
                            if (!is_null($this->fieldHandlers[$field]($resourceItem))) {
                                $item[$this->getFieldName($field)] = $this->fieldHandlers[$field]($resourceItem);
                            }
                        }
                    }

                    $response[] = $item;

                }
            }

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            if ($this->debug) {
                $this->clerk_logger->log('Fetched page '.$this->page.' with '.count($response).' Orders', ['response' => $response]);
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->clerk_logger->log('Fetched page '.$this->page.' with '.count($response).' Orders', ['response' => $response]);
                $this->getResponse()->setBody(json_encode($response));
            }
        } catch (\Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->setHeader('Content-Type', 'application/json', true)
                ->representJson(
                    json_encode([
                        'error' => [
                            'code' => 500,
                            'message' => 'An exception occured',
                            'description' => $e->getMessage(),
                        ]
                    ])
                );
            $this->clerk_logger->error('AbstractAction execute ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Prepare collection
     *
     * @return mixed
     */
    protected function prepareCollection()
    {

        try {

            $collection = $this->collectionFactory->create();

            $collection->addFieldToSelect('*');

            if ($this->start_date) {

                $collection->setPageSize($this->limit)
                    ->setCurPage($this->page)
                    ->addAttributeToFilter('created_at', ['from'=>$this->start_date, 'to'=>$this->end_date])
                    ->addOrder($this->orderBy, $this->order);
            } else {

                $collection->setPageSize($this->limit)
                    ->setCurPage($this->page)
                    ->addOrder($this->orderBy, $this->order);

            }

            return $collection;

        } catch (\Exception $e) {

            $this->clerk_logger->error('prepareCollection ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get mapped field name
     *
     * @param $field
     * @return mixed
     */
    protected function getFieldName($field)
    {

        try {

            if (isset($this->fieldMap[$field])) {
                return $this->fieldMap[$field];
            }

            return $field;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Field Name ERROR', ['error' => $e->getMessage()]);

        }
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

    public function getWebsites()
    {
        $websiteIds = [];
        foreach ($this->_storeManager->getWebsites() as $website) {
            $websiteId = $website["website_id"];
            array_push($websiteIds, $websiteId);
        }

        return $websiteIds;
    }

    public function getStores()
    {
        $storeids = array_keys($this->_storeManager->getStores(true));
        return $storeids;
    }

    public function getRequestBodyParam($key)
    {
        try {

            $body = $this->_request_api->getBodyParams();
            if($body){
                if(gettype($body) === 'array'){
                    if(array_key_exists($key, $body)){
                        return $body[$key];
                    }
                }
            }

            return false;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Request Body ERROR', ['error' => $e->getMessage()]);

        }

    }
}
