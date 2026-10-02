<?php

namespace Clerk\Clerk\Plugin\CatalogSearch\Controller\Result;

use Clerk\Clerk\Model\Config;
use Magento\CatalogSearch\Controller\Result\Index;
use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class IndexPlugin
{
    const CLERK_SEARCH_LAYOUT_HANDLE = 'clerk_result_index';
    const ONE_COLUMN_PAGE_LAYOUT = '1column';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CatalogSearchHelper
     */
    protected $catalogSearchHelper;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * IndexPlugin constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param PageFactory $resultPageFactory
     * @param CatalogSearchHelper $catalogSearchHelper
     * @param QueryFactory $queryFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $url
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PageFactory $resultPageFactory,
        CatalogSearchHelper $catalogSearchHelper,
        QueryFactory $queryFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $url
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resultPageFactory = $resultPageFactory;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;
        $this->url = $url;
    }

    /**
     * Render the Clerk search page without loading Magento's native search layer.
     *
     * @param Index $subject
     * @param callable $proceed
     * @return Page|mixed
     */
    public function aroundExecute(Index $subject, callable $proceed)
    {
        if (!$this->isClerkSearchEnabled()) {
            return $proceed();
        }

        $query = $this->queryFactory->get();
        $query->setStoreId($this->storeManager->getStore()->getId());

        if ($query->getQueryText() === '') {
            return $proceed();
        }

        if (!$this->catalogSearchHelper->isMinQueryLength()) {
            $redirect = $query->getRedirect();
            if ($redirect && $this->url->getCurrentUrl() !== $redirect) {
                $subject->getResponse()->setRedirect($redirect);
                return;
            }
        }

        $this->catalogSearchHelper->checkNotes();

        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle(self::CLERK_SEARCH_LAYOUT_HANDLE);

        if (!$this->isFacetedSearchEnabled()) {
            $resultPage->getConfig()->setPageLayout(self::ONE_COLUMN_PAGE_LAYOUT);
        }

        $subject->getResponse()->setNoCacheHeaders();

        return $resultPage;
    }

    /**
     * Determine if Clerk search is enabled
     *
     * @return bool
     */
    private function isClerkSearchEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_SEARCH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Determine if Clerk faceted search is enabled
     *
     * @return bool
     */
    private function isFacetedSearchEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_FACETED_SEARCH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}
