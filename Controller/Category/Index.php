<?php

namespace Clerk\Clerk\Controller\Category;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Cms\Helper\Page;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;

class Index extends AbstractAction
{
    /**
     * @var
     */
    protected $clerk_logger;

    /**
     * @var array
     */
    protected $fieldMap = [
        'entity_id' => 'id',
        'parent_id' => 'parent',
    ];

    /**
     * @var string
     */
    protected $eventPrefix = 'clerk_category';

    /**
     * @var PageCollectionFactory
     */
    protected $pageCollectionFactory;

    /**
     * @var Page
     */
    protected $pageHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    protected $moduleList;

    /**
     * Category controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $categoryCollectionFactory
     * @param LoggerInterface $logger
     * @param PageCollectionFactory $pageCollectionFactory
     * @param Page $pageHelper
     * @param StoreManagerInterface $storeManager
     * @param Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CollectionFactory $categoryCollectionFactory,
        LoggerInterface $logger,
        PageCollectionFactory $pageCollectionFactory,
        Page $pageHelper,
        ClerkLogger $ClerkLogger,
        ModuleList $moduleList
    )
    {
        $this->moduleList = $moduleList;
        $this->collectionFactory = $categoryCollectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->pageHelper = $pageHelper;
        $this->storeManager = $storeManager;
        $this->clerk_logger = $ClerkLogger;

        $this->addFieldHandlers();

        parent::__construct($context, $storeManager, $scopeConfig, $logger, $moduleList, $ClerkLogger);
    }

    /**
     * Add field handlers
     */
    protected function addFieldHandlers()
    {
        try {
            //Add parent fieldhandler
            $this->addFieldHandler('parent', function ($item) {
                return $item->getParentId();
            });

            //Add url fieldhandler
            $this->addFieldHandler('url', function ($item) {
                return $item->getUrl();
            });

            //Add subcategories fieldhandler
            $this->addFieldHandler('subcategories', function ($item) {
                $children = $item->getAllChildren(true);
                //Remove own ID from subcategories array
                return array_values(array_diff($children, [$item->getId()]));
            });

        } catch (\Exception $e) {

            $this->clerk_logger->error('Category addFieldHandlers ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Execute request
     */
    public function execute()
    {
        try {

            $collection = $this->prepareCollection();

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
                            $item[$field] = $this->fieldHandlers[$field]($resourceItem);
                        }
                    }

                    $response[] = $item;
                }
            }

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            if ($this->debug) {
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
                $this->clerk_logger->log('Fetched '.$this->page.' with '.count($response).' Categories', ['response' => $response]);
            } else {
                $this->getResponse()->setBody(json_encode($response));
                $this->clerk_logger->log('Fetched page '.$this->page.' with '.count($response).' Categories', ['response' => $response]);
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

            $this->clerk_logger->error('Category execute ERROR', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Prepare collection
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareCollection()
    {
        try {

            $collection = $this->collectionFactory->create();

            $rootCategory = $this->storeManager->getStore()->getRootCategoryId();

            $collection->addFieldToSelect('*');
            $collection->addAttributeToFilter('level', ['gteq' => 2]);
            $collection->addAttributeToFilter('name', ['neq' => null]);
            $collection->addPathsFilter('1/' . $rootCategory . '/%');
            $collection->addFieldToFilter('is_active',array("in"=>array('1')));

            $collection->setPageSize($this->limit)
                ->setCurPage($this->page)
                ->addOrder($this->orderBy, $this->order);

            return $collection;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Category prepareCollection ERROR', ['error' => $e->getMessage()]);

        }
    }
}