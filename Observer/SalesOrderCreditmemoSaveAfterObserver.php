<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
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
     * @param ScopeConfigInterface $scopeConfig
     * @param Api $api
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Api $api,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->api = $api;
    }

    /**
     * Return product from Clerk
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order_id = $creditmemo->getOrderId();
        $order = $this->orderRepository->get($order_id);
        $order_increment_id = $order->getIncrementId();
        $store_id = $order->getStore()->getId();

        if (!$this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION, ScopeInterface::SCOPE_STORE, $store_id)) {
            return;
        }
        foreach ($creditmemo->getAllItems() as $item) {
            $product_id = $item->getProductId();
            $quantity = $item->getQty();
            if ($product_id && $order_increment_id && $quantity !=0) {
                $this->api->returnProduct($order_increment_id, $product_id, $quantity, $store_id);
            }
        }
    }
}
