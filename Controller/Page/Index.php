<?php

namespace Clerk\Clerk\Controller\Page;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageFactory;

class Index extends AbstractAction
{

    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var PageHelper
     */
    protected $_pageHelper;

    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * @var PageRepositoryInterface
     */
    protected $_PageRepositoryInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_SearchCriteriaBuilder;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $_searchCriteriaBuilderFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Index constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param PageRepositoryInterface $PageRepositoryInterface
     * @param SearchCriteriaBuilder $SearchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param ClerkLogger $clerk_logger
     * @param PageHelper $pageHelper
     * @param ProductMetadataInterface $product_metadata
     * @param PageFactory $pageFactory
     * @param RequestApi $request_api
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        PageRepositoryInterface $PageRepositoryInterface,
        SearchCriteriaBuilder $SearchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LoggerInterface $logger,
        ClerkLogger $clerk_logger,
        ModuleList $moduleList,
        PageHelper $pageHelper,
        ProductMetadataInterface $product_metadata,
        PageFactory $pageFactory,
        RequestApi $request_api
    ) {
        $this->_searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->_PageRepositoryInterface = $PageRepositoryInterface;
        $this->_SearchCriteriaBuilder = $SearchCriteriaBuilder;
        $this->clerk_logger = $clerk_logger;
        $this->_scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
        $this->storeManager = $storeManager;
        $this->_pageHelper = $pageHelper;
        $this->_pageFactory = $pageFactory;
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

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {

        try {

            $Include_pages = $this->scopeConfig->getValue(Config::XML_PATH_INCLUDE_PAGES, $this->scope, $this->scopeid);

            $Pages_Additional_Fields = is_string($this->scopeConfig->getValue(Config::XML_PATH_PAGES_ADDITIONAL_FIELDS, $this->scope, $this->scopeid)) ? explode(',', $this->scopeConfig->getValue(Config::XML_PATH_PAGES_ADDITIONAL_FIELDS, $this->scope, $this->scopeid)) : [];

            $pages = [];

            if ($Include_pages) {

                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-Type', 'application/json', true);

                // collection of pages visible on all views
                $pages_default = $this->getPageCollection($this->page, $this->limit, 0);

                foreach ($pages_default->getData() as $page_default) {

                    try {
                        $geturl = $this->_pageHelper->getPageUrl($page_default['page_id']);
                        if ($geturl) {
                            $url = $geturl;
                        } else {
                            continue;
                        }
                        $page['id'] = $page_default['page_id'];
                        $page['type'] = 'cms page';
                        $page['url'] = $url;
                        $page['title'] = $page_default['title'];
                        $page['text'] = $page_default['content'];

                        if (!$this->ValidatePage($page)) {

                            continue;

                        }

                        foreach ($Pages_Additional_Fields as $Pages_Additional_Field) {

                            $Pages_Additional_Field = str_replace(' ', '', $Pages_Additional_Field);

                            if (!empty($page_default[$Pages_Additional_Field])) {

                                $page[$Pages_Additional_Field] = $page_default[$Pages_Additional_Field];

                            }

                        }

                        $pages[] = $page;

                    } catch (\Exception $e) {

                        continue;

                    }

                }

                // collection of pages visible only on this view
                $pages_store = $this->getPageCollection($this->page, $this->limit, $this->scopeid);
                foreach ($pages_store->getData() as $page_store) {

                    try {

                        $geturl = $this->_pageHelper->getPageUrl($page_store['page_id']);
                        if ($geturl) {
                            $url = $geturl;
                        } else {
                            continue;
                        }
                        $page['id'] = $page_store['page_id'];
                        $page['type'] = 'cms page';
                        $page['url'] = $url;
                        $page['title'] = $page_store['title'];
                        $page['text'] = $page_store['content'];

                        if (!$this->ValidatePage($page)) {

                            continue;

                        }

                        foreach ($Pages_Additional_Fields as $Pages_Additional_Field) {

                            $Pages_Additional_Field = str_replace(' ', '', $Pages_Additional_Field);

                            if (!empty($page_store[$Pages_Additional_Field])) {

                                $page[$Pages_Additional_Field] = $page_store[$Pages_Additional_Field];

                            }

                        }

                        $pages[] = $page;

                    } catch (\Exception $e) {

                        continue;

                    }

                }

            }

            $this->getResponse()->setBody(json_encode($pages));

        } catch (\Exception $e) {

            $this->clerk_logger->error('Product execute ERROR', ['error' => $e->getMessage()]);

        }
    }


    public function ValidatePage($Page)
    {

        foreach ($Page as $key => $content) {

            if (empty($content)) {

                return false;

            }

        }

        return true;
    }

    public function getPageCollection($page, $limit, $storeid)
    {

        $store = $this->storeManager->getStore($storeid);
        $collection = $this->_pageFactory->create();
        $collection->addFilter('is_active', 1);
        $collection->addFilter('store_id', $store->getId());
        $collection->addStoreFilter($store);
        $collection->setPageSize($limit);
        $collection->setCurPage($page);
        return $collection;
    }
}
