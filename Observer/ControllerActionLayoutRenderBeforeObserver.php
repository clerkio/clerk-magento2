<?php

namespace Clerk\Clerk\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ControllerActionLayoutRenderBeforeObserver implements ObserverInterface
{
    /**
     * Page layout is set on the real search page by IndexPlugin.
     * This used to build a second page on every search request.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return;
    }
}
