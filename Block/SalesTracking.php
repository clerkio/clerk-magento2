<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Sales\Model\Order;

class SalesTracking extends Template
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Grouped
     */
    private $_productGrouped;

    /**
     * SalesTracking constructor.
     *
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param Session $checkoutSession
     * @param Grouped $productGrouped
     * @param array $data
     */
    public function __construct(
        ConfigHelper $configHelper,
        Context      $context,
        Session      $checkoutSession,
        Grouped      $productGrouped,
        array        $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_productGrouped = $productGrouped;
        $this->configHelper = $configHelper;
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
     * Get last order from session
     *
     * @return Order
     */
    private function getOrder()
    {
        return $this->_checkoutSession->getLastRealOrder();
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        $collect_emails = $this->configHelper->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS);
        if (!$collect_emails) {
            return "";
        }
        return $this->getOrder()->getCustomerEmail();
    }

    /**
     * Get all order products as JSON string
     *
     * @return string
     */
    public function getProducts()
    {
        $order = $this->getOrder();
        $products = [];

        foreach ($order->getAllVisibleItems() as $item) {
            $group_parent_id = $this->_productGrouped->getParentIdsByChild($item->getProductId());
            $product_id = !empty($group_parent_id) ? $group_parent_id[0] : $item->getProductId();
            $product = [
                'id' => $product_id,
                'quantity' => (int)$item->getQtyOrdered(),
                'price' => (float)$item->getBasePrice(),
            ];
            $products[] = $product;
        }

        return json_encode($products);
    }
}
