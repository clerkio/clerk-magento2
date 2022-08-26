<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

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

    public function __construct(ScopeConfigInterface $scopeConfig, Api $api, OrderRepositoryInterface $orderRepository)
    {
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
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION, ScopeInterface::SCOPE_STORE)) {

            $creditmemo = $observer->getEvent()->getCreditmemo();
            $order_id = $creditmemo->getOrderId();
            $order = $this->orderRepository->get($order_id);
            $orderIncrementId = $order->getIncrementId();
           
            foreach ($creditmemo->getAllItems() as $item) {

                $product_id = $item->getProductId();
                $quantity = $item->getQty();

                if ($product_id && $orderIncrementId && $quantity !=0 ) {
                    $this->api->returnProduct($orderIncrementId, $product_id, $quantity);
                }

            }
        }
    }
}
