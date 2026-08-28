<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\ProductRealtimeSynchronizer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class QuoteSubmitSuccessObserver implements ObserverInterface
{
    /**
     * @var ProductRealtimeSynchronizer
     */
    protected $productRealtimeSynchronizer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ProductRealtimeSynchronizer $productRealtimeSynchronizer,
        LoggerInterface $logger
    ) {
        $this->productRealtimeSynchronizer = $productRealtimeSynchronizer;
        $this->logger = $logger;
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

        try {
            $this->productRealtimeSynchronizer->syncProductIds($productIds, $order->getStoreId());
        } catch (\Exception $e) {
            $this->logger->error('Clerk realtime product sync after order failed: ' . $e->getMessage());
        }
    }
}
