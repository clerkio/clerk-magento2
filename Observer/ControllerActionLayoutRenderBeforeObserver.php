<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Result\PageFactory;

class ControllerActionLayoutRenderBeforeObserver implements ObserverInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * LayoutGenerateBlocksAfterObserver constructor.
     *
     * @param ConfigHelper $configHelper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        ConfigHelper $configHelper,
        PageFactory $resultPageFactory
    ) {
        $this->configHelper = $configHelper;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Event observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
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
    private function isFacetedSearchEnabled()
    {
        return $this->configHelper->getFlag(Config::XML_PATH_FACETED_SEARCH_ENABLED);
    }

    /**
     * Determine if Clerk search is enabled
     *
     * @return bool
     */
    private function isClerkSearchEnabled()
    {
        return $this->configHelper->getFlag(Config::XML_PATH_SEARCH_ENABLED);
    }
}
