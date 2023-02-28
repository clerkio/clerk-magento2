<?php

namespace Clerk\Clerk\Block;

use Clerk\Clerk\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\ScopeInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

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
     * @param Grouped $productGrouped
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Grouped $productGrouped,
        array $data = []
        )
    {
        parent::__construct(
            $context,
            $data
        );
        $this->_checkoutSession = $checkoutSession;
        $this->_productGrouped = $productGrouped;
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
        if ($this->_storeManager->isSingleStoreMode()) {
            $scope = 'default';
            $scope_id = '0';
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $scope_id = $this->_storeManager->getStore()->getId();
        }

        $collect_emails = $this->_scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS, $scope, $scope_id);
        if ($collect_emails == '1') {
            return $this->getOrder()->getCustomerEmail();
        } else {
            return "";
        }
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
            $groupParentId = $this->_productGrouped->getParentIdsByChild($item->getProductId());

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
