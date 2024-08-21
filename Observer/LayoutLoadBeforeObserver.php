<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;

class LayoutLoadBeforeObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * LayoutGenerateBlocksAfterObserver constructor.
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * Event observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Layout $layout */
        $layout = $observer->getLayout();
        $actionName = $observer->getFullActionName();

        //Add custom layout handle if clerk search is enabled
        if ($this->isClerkSearchEnabled() && $actionName === 'catalogsearch_result_index') {
            $layout->getUpdate()->addHandle('clerk_result_index');
        }
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
