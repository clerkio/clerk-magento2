<?php

namespace Clerk\Clerk\Controller\Page;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;

class Index extends AbstractAction
{
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
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    protected $_scopeConfig;

    protected $moduleList;

    /**
     * Index constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param PageRepositoryInterface $PageRepositoryInterface
     * @param SearchCriteriaBuilder $SearchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param ClerkLogger $ClerkLogger
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        PageRepositoryInterface $PageRepositoryInterface,
        SearchCriteriaBuilder $SearchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        ClerkLogger $ClerkLogger,
        ModuleList $moduleList
    )
    {
        $this->_searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->_PageRepositoryInterface = $PageRepositoryInterface;
        $this->_SearchCriteriaBuilder = $SearchCriteriaBuilder;
        $this->_objectManager = $objectManager;
        $this->clerk_logger = $ClerkLogger;
        $this->_scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
        parent::__construct($context, $storeManager, $scopeConfig, $logger, $moduleList, $ClerkLogger);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {

        try {

            $Include_pages = $this->scopeConfig->getValue(Config::XML_PATH_INCLUDE_PAGES, ScopeInterface::SCOPE_STORE);
            $Pages_Additional_Fields = explode(',',$this->scopeConfig->getValue(Config::XML_PATH_PAGES_ADDITIONAL_FIELDS, ScopeInterface::SCOPE_STORE));

            $pages = [];

            if ($Include_pages) {

                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-Type', 'application/json', true);

                $searchCriteriaBuilder = $this->_searchCriteriaBuilderFactory->create();
                $searchCriteriaBuilder->addFilter('is_active', 1, 'eq');
                $searchCriteria = $searchCriteriaBuilder->create();
                $pages_raw = $this->_PageRepositoryInterface->getList($searchCriteria)->getItems();

                foreach ($pages_raw as $page_raw) {

                    try {

                        $url = $this->_objectManager->create('Magento\Cms\Helper\Page')
                            ->getPageUrl($page_raw['page_id']);
                        $page['id'] = $page_raw['page_id'];
                        $page['type'] = 'cms page';
                        $page['url'] = $url;
                        $page['title'] = $page_raw['title'];
                        $page['text'] = $page_raw['content'];

                        if (!$this->ValidatePage($page)) {

                            continue;

                        }

                        foreach ($Pages_Additional_Fields as $Pages_Additional_Field) {

                            $Pages_Additional_Field = str_replace(' ','',$Pages_Additional_Field);

                            if (!empty($page_raw[$Pages_Additional_Field])) {

                                $page[$Pages_Additional_Field] = $page_raw[$Pages_Additional_Field];

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


    public function ValidatePage($Page) {

        foreach ($Page as $key => $content) {

            if (empty($content)) {

                return false;

            }

        }

        return true;

    }

}
