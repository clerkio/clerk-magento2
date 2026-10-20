<?php

namespace Clerk\Clerk\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Runs realtime syncs as a guest, so the shared Clerk catalogue never gets the logged-in buyer's prices.
 *
 * Catalog price rules and tax read the customer group, tax addresses and customer ID from the customer session.
 */
class GuestCustomerContext
{
    /**
     * Session keys that catalog price rules and tax read.
     */
    const SESSION_KEYS = [
        'customer_id',
        'customer_group_id',
        'default_tax_shipping_address',
        'default_tax_billing_address',
    ];

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerInterface|null
     */
    protected $customer;

    /**
     * @var bool
     */
    protected $captured = false;

    public function __construct(
        CustomerSession $customerSession,
        CustomerInterfaceFactory $customerFactory
    ) {
        $this->customerSession = $customerSession;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Note the logged-in buyer while the request is still running.
     *
     * The session is a proxy; starting it for the first time after the response is sent would fail.
     *
     * @return void
     */
    public function capture()
    {
        if ($this->captured) {
            return;
        }
        $this->captured = true;
        $this->customer = $this->customerSession->getCustomerId() ? $this->customerSession->getCustomerData() : null;
    }

    /**
     * Run $callback with the customer session switched to a guest, then restore it.
     *
     * @param callable $callback
     * @return mixed
     */
    public function run(callable $callback)
    {
        if (!$this->customer) {
            return $callback();
        }

        $session = $this->customerSession;
        $saved = [];
        foreach (self::SESSION_KEYS as $key) {
            $saved[$key] = $session->getData($key);
        }

        $guest = $this->customerFactory->create();
        $guest->setGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        // Replaces the cached customer too; getCustomerGroupId() falls back to it when the stored group is 0.
        $session->setCustomerData($guest);
        foreach (self::SESSION_KEYS as $key) {
            $session->unsetData($key);
        }

        try {
            return $callback();
        } finally {
            $session->setCustomerData($this->customer);
            foreach ($saved as $key => $value) {
                if ($value === null) {
                    $session->unsetData($key);
                } else {
                    $session->setData($key, $value);
                }
            }
        }
    }
}
