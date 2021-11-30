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

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        foreach ($order->getAllVisibleItems() as $item) {
            $groupParentId = $objectManager->create('Magento\GroupedProduct\Model\Product\Type\Grouped')->getParentIdsByChild($item->getProductId());

            if (isset($groupParentId[0])) {

                $product = [
                    'id' => $groupParentId[0],
                    'quantity' => (int)$item->getQtyOrdered(),
                    'price' => (float)$item->getBasePrice(),
                ];

            } else {

                $product = [
                    'id'       => $item->getProductId(),
                    'quantity' => (int) $item->getQtyOrdered(),
                    'price'    => (float) $item->getBasePrice(),
                ];
            }

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