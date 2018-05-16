<?php

namespace Clerk\Clerk\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;

class SalesTracking extends Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * SalesTracking constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(Context $context, Session $checkoutSession, array $data = [])
    {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Get order increment id
     *
     * @return string
     */
    public function getIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->getOrder()->getCustomerEmail();
    }

    /**
     * Get all order products as json string
     *
     * @return string
     */
    public function getProducts()
    {
        $order = $this->getOrder();
        $products = [];

        foreach ($order->getAllVisibleItems() as $item) {
            $product = [
                'id'       => $item->getProductId(),
                'quantity' => (int) $item->getQtyOrdered(),
                'price'    => (float) $item->getBasePrice(),
            ];

            $products[] = $product;
        }

        return json_encode($products);
    }

    /**
     * Get last order from session
     *
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        return $this->_checkoutSession->getLastRealOrder();
    }
}