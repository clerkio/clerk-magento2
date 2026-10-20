<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\DeferredProductSync;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class QuoteSubmitSuccessObserver implements ObserverInterface
{
    /**
     * @var DeferredProductSync
     */
    protected $deferredProductSync;

    public function __construct(
        DeferredProductSync $deferredProductSync
    ) {
        $this->deferredProductSync = $deferredProductSync;
    }

    /**
     * Sync purchased products so Clerk stock matches Magento after order placement.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            return;
        }

        $productIds = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductId()) {
                $productIds[] = $item->getProductId();
            }
        }

        if (empty($productIds)) {
            return;
        }

        // Deferred so a slow Clerk call cannot hold checkout while the quote is still active.
        $this->deferredProductSync->add($productIds, $order->getStoreId());
    }
}
