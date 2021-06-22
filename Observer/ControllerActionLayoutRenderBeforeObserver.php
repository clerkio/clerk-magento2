<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;

class ControllerActionLayoutRenderBeforeObserver implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * LayoutGenerateBlocksAfterObserver constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PageFactory $resultPageFactory
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //Change page layout if faceted search is enabled
        if (!$this->isFacetedSearchEnabled() && $this->isClerkSearchEnabled()) {
            $resultPage = $this->resultPageFactory->create();
            $pageConfig = $resultPage->getConfig();
            $pageConfig->setPageLayout('1column');
        }
    }

    /**
     * Determine if Clerk search is enabled
     *
     * @return bool
     */
    private function isClerkSearchEnabled()
    {
        return $this->scopeConfig->isSetFlag(Config::XML_PATH_SEARCH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Determine if Clerk search is enabled
     *
     * @return bool
     */
    private function isFacetedSearchEnabled()
    {
        return $this->scopeConfig->isSetFlag(Config::XML_PATH_FACETED_SEARCH_ENABLED, ScopeInterface::SCOPE_STORE);
    }
}
