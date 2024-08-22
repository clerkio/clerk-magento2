<?php

namespace Clerk\Clerk\Controller;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractAction extends Action
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var RequestApi
     */
    protected $requestApi;

    /**
     * @var ClerkLogger
     */
    protected $clerkLogger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

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
     * @var int
     */
    protected $start_date;

    /**
     * @var int
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
    protected $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var int
     */
    protected $scopeid;
    /**
     * @var string
     */
    protected $scope;
    /**
     * @var string|null
     */
    private $privateKey;
    /**
     * @var string|null
     */
    private $publicKey;

    /**
     * AbstractAction constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ModuleList $moduleList
     * @param ClerkLogger $clerkLogger
     * @param ProductMetadataInterface $productMetadata
     * @param RequestApi $requestApi
     * @param Api $api
     */
    public function __construct(
        Context                  $context,
        StoreManagerInterface    $storeManager,
        ScopeConfigInterface     $scopeConfig,
        LoggerInterface          $logger,
        ModuleList               $moduleList,
        ClerkLogger              $clerkLogger,
        ProductMetadataInterface $productMetadata,
        RequestApi               $requestApi,
        Api                      $api
    )
    {
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->clerkLogger = $clerkLogger;
        $this->productMetadata = $productMetadata;
        $this->requestApi = $requestApi;
        $this->api = $api;
        parent::__construct($context);
    }

    /**
     * Execute request
     * @throws FileSystemException
     * @noinspection PhpPossiblePolymorphicInvocationInspection
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
                $this->clerkLogger->log('Fetched page ' . $this->page . ' with ' . count($response) . ' Orders', ['response' => $response]);
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->clerkLogger->log('Fetched page ' . $this->page . ' with ' . count($response) . ' Orders', ['response' => $response]);
                $this->getResponse()->setBody(json_encode($response));
            }
        } catch (Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->setHeader('Content-Type', 'application/json', true)
                ->representJson(
                    json_encode([
                        'error' => [
                            'code' => 500,
                            'message' => 'An exception occurred',
                            'description' => $e->getMessage(),
                        ]
                    ])
                );
            $this->clerkLogger->error('AbstractAction execute ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Prepare collection
     *
     * @return object|void
     * @throws FileSystemException
     */
    protected function prepareCollection()
    {

        try {

            $collection = $this->collectionFactory->create();

            $collection->addFieldToSelect('*');

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

            return $collection;

        } catch (Exception $e) {

            $this->clerkLogger->error('prepareCollection ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return ResponseInterface|void
     * @throws FileSystemException
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function dispatch(RequestInterface $request)
    {

        try {

            $version = $this->productMetadata->getVersion();
            header('User-Agent: ClerkExtensionBot Magento 2/v' . $version . ' clerk/v' . $this->moduleList->getOne('Clerk_Clerk')['setup_version'] . ' PHP/v' . phpversion());

            $this->publicKey = $this->getRequestBodyParam('key');
            $this->privateKey = $this->getRequestBodyParam('private_key');

            $identity = $this->identifyScope();
            $authorized = $this->authorize($identity);

            if (!$authorized || empty($identity)) {
                $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
                $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);

                //Display error
                $this->getResponse()
                    ->setHttpResponseCode(403)
                    ->representJson(
                        json_encode([
                            'error' => [
                                'code' => 403,
                                'message' => __(' Invalid Authentication, please provide valid credentials.'),
                            ]
                        ])
                    );

                $this->clerkLogger->warn('Invalid keys supplied', ['response' => parent::dispatch($request)]);

                return parent::dispatch($request);
            }

            $request->setParams(['scope_id' => $identity['scope_id']]);
            $request->setParams(['scope' => $identity['scope']]);

            //Filter out request arguments
            $this->getArguments($request);
            return parent::dispatch($request);

        } catch (Exception $e) {
            $this->clerkLogger->error('Validating API Keys ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param $key
     * @return mixed|void
     */
    public function getRequestBodyParam($key)
    {
        try {

            $body = $this->requestApi->getBodyParams();

            if ($body && is_array($body) && array_key_exists($key, $body)) {
                return $body[$key];
            }

        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Request Body ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return array
     */
    private function identifyScope()
    {
        $scope_info = [];
        if (!$this->publicKey) {
            return $scope_info;
        }

        $website = $this->verifyWebsiteKeys();
        $store = $this->verifyKeys();
        $default = $this->verifyDefaultKeys();

        if (null !== $website) {
            $scope_info = [
                'scope_id' => $website,
                'scope' => 'website'
            ];
        }
        if (null !== $store) {
            $scope_info = [
                'scope_id' => $store,
                'scope' => 'store'
            ];
        }
        if (null !== $default && $this->storeManager->isSingleStoreMode()) {
            $scope_info = [
                'scope_id' => $default,
                'scope' => 'default'
            ];
        }
        return $scope_info;
    }

    /**
     * Verify public & private key
     *
     * @return int|void
     */
    private function verifyWebsiteKeys()
    {
        try {
            $websiteids = $this->getWebsites();
            foreach ($websiteids as $scopeID) {
                if ($this->timingSafeEquals($this->getPublicWebsiteKey($scopeID), $this->publicKey)) {
                    return $scopeID;
                }
            }
        } catch (Exception $e) {
            $this->clerkLogger->error('verifyKeys ERROR', ['error' => $e->getMessage()]);
        }
    }

    public function getWebsites()
    {
        $websiteIds = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            $websiteId = $website["website_id"];
            $websiteIds[] = $websiteId;
        }

        return $websiteIds;
    }

    /**
     * Timing safe key comparison
     *
     * @param string $safe
     * @param string $user
     * @return boolean
     */
    private function timingSafeEquals(string $safe, string $user)
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
     * Get public website key
     *
     * @param $scopeID
     * @return string|void
     */
    private function getPublicWebsiteKey($scopeID)
    {
        try {
            return $this->scopeConfig->getValue(
                Config::XML_PATH_PUBLIC_KEY,
                ScopeInterface::SCOPE_WEBSITES,
                $scopeID
            );
        } catch (Exception $e) {
            $this->clerkLogger->error('getPublicKey ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Verify public & private key
     *
     * @return int|void
     */
    private function verifyKeys()
    {

        try {

            $storeids = $this->getStores();
            foreach ($storeids as $scopeID) {
                if ($this->timingSafeEquals($this->getPublicKey($scopeID), $this->publicKey)) {
                    return $scopeID;
                }
            }

        } catch (Exception $e) {

            $this->clerkLogger->error('verifyKeys ERROR', ['error' => $e->getMessage()]);

        }
    }

    public function getStores()
    {
        return array_keys($this->storeManager->getStores(true));
    }

    /**
     * Get the public store key
     *
     * @param $scopeID
     * @return string|void
     */
    private function getPublicKey($scopeID)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PUBLIC_KEY,
                ScopeInterface::SCOPE_STORES,
                $scopeID
            );

        } catch (Exception $e) {

            $this->clerkLogger->error('getPublicKey ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Verify public & private key
     *
     * @return int|void
     */
    private function verifyDefaultKeys()
    {

        try {

            $scopeID = $this->storeManager->getDefaultStoreView()->getId();
            if ($this->timingSafeEquals($this->getPublicDefaultKey($scopeID), $this->publicKey)) {
                return $scopeID;
            }

        } catch (Exception $e) {

            $this->clerkLogger->error('verifyKeys ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get the public store key
     *
     * @param $scopeID
     * @return string|void
     */
    private function getPublicDefaultKey($scopeID)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PUBLIC_KEY,
                ScopeInterface::SCOPE_STORE,
                $scopeID
            );

        } catch (Exception $e) {

            $this->clerkLogger->error('getPublicKey ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * @throws Exception
     */
    private function authorize($scope_info)
    {
        if (empty($scope_info)) {
            return false;
        }
        if (!array_key_exists('scope_id', $scope_info) || !array_key_exists('scope', $scope_info)) {
            return false;
        }

        $legacy_auth = $this->scopeConfig->getValue(
            Config::XML_PATH_USE_LEGACY_AUTH,
            $scope_info['scope'],
            $scope_info['scope_id']
        );

        if (!$legacy_auth) {
            // check Header Token
            return $this->validateJwt();
        }

        if (!$this->privateKey) {
            return false;
        }
        $private_key = $this->getPrivateKey($scope_info['scope'], $scope_info['scope_id']);
        return $this->timingSafeEquals($private_key, $this->privateKey);
    }

    /**
     * Validate token with Clerk
     *
     * @return bool
     * @throws Exception
     */
    public function validateJwt()
    {

        $token_string = $this->getHeaderToken();

        if (!$token_string) {
            return false;
        }

        $rsp_array = $this->api->verifyToken($token_string, $this->publicKey);

        if (!is_array($rsp_array)) {
            return false;
        }

        if (!array_key_exists('status', $rsp_array)) {
            return false;
        }

        if ($rsp_array['status'] !== 'ok') {
            return false;
        }

        return true;
    }

    /**
     * Get Token from Request Header
     * @return string
     */
    private function getHeaderToken()
    {
        try {

            $token = '';
            $auth_header = $this->requestApi->getHeader('X-Clerk-Authorization');

            if (!is_string($auth_header)) {
                return "";
            }

            $auth_header_array = explode(' ', $auth_header);
            if (count($auth_header_array) !== 2 || $auth_header_array[0] !== 'Bearer') {
                return "";
            }

            $token = $auth_header_array[1];

        } catch (Exception $e) {

            $this->logger->error('getHeaderToken ERROR', ['error' => $e->getMessage()]);

        }
        return $token;
    }

    /**
     * Get the private store key
     *
     * @param string $scope
     * @param int $scope_id
     * @return string|void
     */
    private function getPrivateKey($scope, $scope_id)
    {
        try {

            return $this->scopeConfig->getValue(
                Config::XML_PATH_PRIVATE_KEY,
                $scope,
                $scope_id
            );

        } catch (Exception $e) {
            $this->clerkLogger->error('getPrivateKey ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Parse request arguments
     *
     * @param RequestInterface $request
     * @return void
     */
    protected function getArguments(RequestInterface $request)
    {
        try {

            $this->debug = (bool)$request->getParam('debug', false);
            $startDate = strtotime('today - 200 years');
            $startDateParam = $request->getParam('start_date');
            if (!empty($startDateParam)) {
                if (is_int($startDateParam)) {
                    $startDate = $startDateParam;
                } else {
                    $startDate = strtotime($startDateParam);
                }
            }
            $endDate = strtotime('today + 1 day');
            $endDateParam = $request->getParam('end_date');
            if (!empty($endDateParam)) {
                if (is_int($endDateParam)) {
                    $endDate = $endDateParam;
                } else {
                    $endDate = strtotime($endDateParam);
                }
            }
            $this->start_date = date('Y-m-d', $startDate);
            $this->end_date = date('Y-m-d', $endDate);
            $this->limit = (int)$request->getParam('limit', 0);
            $this->page = (int)$request->getParam('page', 0);
            $this->orderBy = $request->getParam('orderby', 'entity_id');
            $this->order = $request->getParam('order', 'asc');
            $this->limit = (int)$request->getParam('limit', 0);
            $this->page = (int)$request->getParam('page', 0);
            $this->orderBy = $request->getParam('orderby', 'entity_id');
            $this->scope = $request->getParam('scope');
            $this->scopeid = $request->getParam('scope_id');

            $this->order = $request->getParam('order') === 'desc' ? Collection::SORT_ORDER_DESC : Collection::SORT_ORDER_ASC;

            /**
             * Explode fields on ',' and filter out "empty" entries
             */
            $fields = $request->getParam('fields');
            $this->fields = $fields ? array_filter(explode(',', $fields), 'strlen') : $this->getDefaultFields();
            $this->fields = array_merge(['entity_id'], $this->fields);

            foreach ($this->fields as $key => $field) {

                $this->fields[$key] = str_replace(' ', '', $field);

            }

        } catch (Exception $e) {
            $this->clerkLogger->error('getArguments ERROR', ['error' => $e->getMessage()]);
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
     * Get mapped field name
     *
     * @param string $field
     * @return mixed
     */
    protected function getFieldName($field)
    {
        try {
            if (isset($this->fieldMap[$field])) {
                return $this->fieldMap[$field];
            }
        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Field Name ERROR', ['error' => $e->getMessage()]);
        }
        return $field;
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
}
