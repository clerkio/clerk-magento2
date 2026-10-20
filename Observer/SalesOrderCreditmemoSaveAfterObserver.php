<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\DeferredProductSync;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;

class SalesOrderCreditmemoSaveAfterObserver implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var DeferredProductSync
     */
    protected $deferredProductSync;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Api $api,
        OrderRepositoryInterface $orderRepository,
        DeferredProductSync $deferredProductSync
    ) {
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->api = $api;
        $this->deferredProductSync = $deferredProductSync;
    }

    /**
     * Track returned products in Clerk and resync stock after a credit memo.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        if (!$creditmemo) {
            return;
        }

        $order = $this->orderRepository->get($creditmemo->getOrderId());
        $orderIncrementId = $order->getIncrementId();
        $storeId = $order->getStore()->getId();
        $productIds = [];

        $trackReturns = $this->scopeConfig->getValue(
            Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        foreach ($creditmemo->getAllItems() as $item) {
            $productId = $item->getProductId();
            if (!$productId) {
                continue;
            }

            $productIds[] = $productId;
            $quantity = $item->getQty();

            if ($trackReturns && $orderIncrementId && $quantity != 0) {
                $this->api->returnProduct($orderIncrementId, $productId, $quantity, $storeId);
            }
        }

        if (empty($productIds)) {
            return;
        }

        $this->deferredProductSync->add($productIds, $storeId);
    }
}
