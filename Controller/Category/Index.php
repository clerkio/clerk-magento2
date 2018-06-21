<?php

namespace Clerk\Clerk\Controller\Category;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Config;
use Magento\Cms\Helper\Page;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
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
     * Category controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $categoryCollectionFactory,
        LoggerInterface $logger,
        PageCollectionFactory $pageCollectionFactory,
        Page $pageHelper
    )
    {
        $this->collectionFactory = $categoryCollectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->pageHelper = $pageHelper;

        $this->addFieldHandlers();

        parent::__construct($context, $scopeConfig, $logger);
    }

    /**
     * Add field handlers
     */
    protected function addFieldHandlers()
    {
        //Add parent fieldhandler
        $this->addFieldHandler('parent', function($item) {
            return $item->getParentId();
        });

        //Add url fieldhandler
        $this->addFieldHandler('url', function($item) {
            return $item->getUrl();
        });

        //Add subcategories fieldhandler
        $this->addFieldHandler('subcategories', function($item) {
            $children = $item->getAllChildren(true);
            //Remove own ID from subcategories array
            return array_values(array_diff($children, [$item->getId()]));
        });
    }

    /**
     * Prepare collection
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareCollection()
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToSelect('*');
        $collection->addAttributeToFilter('level', ['gteq' => 2]);
        $collection->addAttributeToFilter('name', ['neq' => null]);

        $collection->setPageSize($this->limit)
                   ->setCurPage($this->page)
                   ->addOrder($this->orderBy, $this->order);

        return $collection;
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

            //Append CMS pages as dummy categories
            if ($this->scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_INCLUDE_CMS_PAGES)) {
                $pageCollection = $this->pageCollectionFactory->create();
                $pageCollection->addFieldToSelect('*');
                $pageCollection->addFieldToFilter('is_active', \Magento\Cms\Model\Page::STATUS_ENABLED);

                foreach ($pageCollection as $pageItem) {
                    $response[] = [
                        'id' => $pageItem->getId() + 10000, //Add 10000 to avoid category ID collisions
                        'name' => $pageItem->getTitle(),
                        'url' => $this->pageHelper->getPageUrl($pageItem->getId()),
                    ];
                }
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
}