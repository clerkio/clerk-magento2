<?php

namespace Clerk\Clerk\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractAction extends Action
{
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
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var string
     */
    protected $clerkEventPrefix = '';

    /**
     * Constructor
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, LoggerInterface $logger)
    {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->eventManager = $this->getEventManager();

        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        //Validate supplied keys
        if (!$this->verifyKeys($request)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);

            //Display error
            $this->getResponse()
                ->setHttpResponseCode(403)
                ->representJson(
                    json_encode([
                        'code'        => 403,
                        'message'     => 'Invalid keys supplied',
                        'description' => 'The supplied public or private key is invalid',
                        'how_to_fix'  => 'Ensure that the proper keys are set up in the configuration',
                    ])
                );

            return parent::dispatch($request);
        }

        //Filter out request arguments
        $this->getArguments($request);

        return parent::dispatch($request);
    }

    /**
     * Verify public & private key
     *
     * @param RequestInterface $request
     * @return bool
     */
    private function verifyKeys(RequestInterface $request)
    {
        $privateKey = $request->getParam('private_key');
        $publicKey = $request->getParam('key');

        if ($privateKey !== $this->getPrivateKey() || $publicKey !== $this->getPublicKey()) {
            return false;
        }

        return true;
    }

    /**
     * Get private key
     *
     * @return string
     */
    private function getPrivateKey()
    {
        return $this->scopeConfig->getValue(
            \Clerk\Clerk\Model\Config::XML_PATH_PRIVATE_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get public key
     *
     * @return string
     */
    private function getPublicKey()
    {
        return $this->scopeConfig->getValue(
            \Clerk\Clerk\Model\Config::XML_PATH_PUBLIC_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Parse request arguments
     */
    private function getArguments(RequestInterface $request)
    {
        $this->debug = (bool) $request->getParam('debug', false);
        $this->limit = (int) $request->getParam('limit', 0);
        $this->page = (int) $request->getParam('page', 0);
        $this->orderBy = $request->getParam('orderby', 'entity_id');

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
    }

    /**
     * Execute request
     */
    public function execute()
    {
        try {
            $collection = $this->prepareCollection();
            
            $this->eventManager->dispatch($this->clerkEventPrefix .'_before_response_build', [
                'controller' => $this,
                'collection' => $collection
            ]);

            $response = [];

            //Build response
            foreach ($collection as $resourceItem) {
                $item = [];

                foreach ($this->fields as $field) {
                    if (isset($resourceItem[$field])) {
                        $item[$this->getFieldName($field)] = $resourceItem[$field];
                    }

                    if (isset($this->fieldHandlers[$field])) {
                        $item[$field] = $this->fieldHandlers[$field]($resourceItem);
                    }
                }

                $response[] = $item;
            }

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            if ($this->debug) {
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->getResponse()->setBody(json_encode($response));
            }
        } catch (\Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->setHeader('Content-Type', 'application/json', true)
                ->representJson(
                    json_encode([
                        'code'        => 500,
                        'message'     => 'An exception occured',
                        'description' => $e->getMessage(),
                        'how_to_fix'  => 'Please report this error to the clerk support team',
                    ])
                );
        }
    }

    /**
     * Get mapped field name
     *
     * @param $field
     * @return mixed
     */
    private function getFieldName($field)
    {
        if (isset($this->fieldMap[$field])) {
            return $this->fieldMap[$field];
        }

        return $field;
    }

    /**
     * Prepare collection
     *
     * @return mixed
     */
    protected function prepareCollection()
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToSelect('*');

        $collection->setPageSize($this->limit)
            ->setCurPage($this->page)
            ->addOrder($this->orderBy, $this->order);

        return $collection;
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

    /**
     * Get default fields
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        return [];
    }
}