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
     * Return product from Clerk and update order totals
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
            $store_id = $order->getStore()->getId();

            // Process individual product returns
            foreach ($creditmemo->getAllItems() as $item) {

                $product_id = $item->getProductId();
                $quantity = $item->getQty();

                if ($product_id && $orderIncrementId && $quantity !=0) {
                    $this->api->returnProduct($orderIncrementId, $product_id, $quantity, $store_id);
                }

            }

            // Update order with new net totals after refund
            $this->updateOrderInClerk($order, $store_id);
        }
    }

    /**
     * Update order data in Clerk with current net values after refund
     *
     * @param \Magento\Sales\Model\Order $order
     * @param int $store_id
     * @return void
     */
    protected function updateOrderInClerk($order, $store_id)
    {
        try {
            // Calculate updated net total after refund
            $netTotal = $order->getGrandTotal() - $order->getTotalRefunded();
            
            // Prepare updated order data
            $orderData = [
                'id' => $order->getIncrementId(),
                'total' => (float) $netTotal,
                'refunded_amount' => (float) $order->getTotalRefunded(),
                'discount_amount' => (float) abs($order->getDiscountAmount()),
                'shipping_amount' => (float) $order->getShippingAmount(),
                'tax_amount' => (float) $order->getTaxAmount(),
                'time' => strtotime($order->getCreatedAt()),
            ];

            // Add customer info if email collection is enabled
            if ($this->scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS, ScopeInterface::SCOPE_STORE, $store_id)) {
                $orderData['email'] = $order->getCustomerEmail();
            }

            $orderData['customer'] = $order->getCustomerId();

            // Update products with net prices
            $products = [];
            foreach ($order->getAllVisibleItems() as $productItem) {
                $netPrice = $this->calculateNetProductPrice($productItem);
                $products[] = [
                    'id' => $productItem->getProductId(),
                    'quantity' => (int) $productItem->getQtyOrdered(),
                    'price' => (float) $netPrice,
                ];
            }
            $orderData['products'] = $products;

            // Send updated order data to Clerk
            $this->api->updateOrder($orderData, $store_id);

        } catch (\Exception $e) {
            // Log error but don't break the refund process
            error_log('Clerk order update error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate net price per product unit considering discounts and tax
     *
     * @param \Magento\Sales\Model\Order\Item $productItem
     * @return float
     */
    protected function calculateNetProductPrice($productItem)
    {
        try {
            // Get the row total (price * quantity) after discounts
            $rowTotal = $productItem->getRowTotal();
            $discountAmount = abs($productItem->getDiscountAmount());
            $taxAmount = $productItem->getTaxAmount();
            $quantity = $productItem->getQtyOrdered();

            if ($quantity <= 0) {
                return 0.0;
            }

            // Calculate net row total: base price - discounts + tax (if tax-inclusive store)
            $netRowTotal = $rowTotal - $discountAmount;
            
            // For tax-inclusive stores, we need to include tax in the net price
            // For tax-exclusive stores, the net price should exclude tax
            $order = $productItem->getOrder();
            $store = $order->getStore();
            
            // Check if prices include tax in the store configuration
            $pricesIncludeTax = $this->scopeConfig->getValue(
                'tax/calculation/price_includes_tax',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store->getId()
            );

            if (!$pricesIncludeTax) {
                // For tax-exclusive stores, add tax to get the final customer-paid amount
                $netRowTotal += $taxAmount;
            }

            // Return net price per unit
            return $netRowTotal / $quantity;

        } catch (\Exception $e) {
            // Fallback to original price if calculation fails
            return (float) $productItem->getPrice();
        }
    }
}
