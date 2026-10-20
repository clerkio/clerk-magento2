<?php

namespace Clerk\Clerk\Test\Unit\Model;

use Clerk\Clerk\Model\GuestCustomerContext;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Session;
use PHPUnit\Framework\TestCase;

class GuestCustomerContextTest extends TestCase
{
    public function testLoggedInBuyerLooksLikeAGuestDuringTheSyncAndIsRestoredAfter(): void
    {
        $vip = $this->customer(7, 5);
        $session = $this->session($vip, [
            'customer_id' => 7,
            'default_tax_shipping_address' => ['country_id' => 'US', 'region_id' => 57],
            'default_tax_billing_address' => ['country_id' => 'US', 'region_id' => 57],
        ]);
        $context = new GuestCustomerContext($session, $this->customerFactory());

        $context->capture();
        $seen = $context->run(function () use ($session) {
            return [
                'customer_id' => $session->getCustomerId(),
                'group_id' => $session->getCustomerGroupId(),
                'shipping' => $session->getDefaultTaxShippingAddress(),
                'billing' => $session->getDefaultTaxBillingAddress(),
            ];
        });

        $this->assertSame(['customer_id' => null, 'group_id' => 0, 'shipping' => null, 'billing' => null], $seen);
        $this->assertSame(7, $session->getCustomerId());
        $this->assertSame(5, $session->getCustomerGroupId());
        $this->assertSame($vip, $session->getCustomerData());
        $this->assertSame(['country_id' => 'US', 'region_id' => 57], $session->getDefaultTaxShippingAddress());
    }

    public function testSessionIsRestoredWhenTheSyncThrows(): void
    {
        $vip = $this->customer(7, 5);
        $session = $this->session($vip, ['customer_id' => 7, 'customer_group_id' => 5]);
        $context = new GuestCustomerContext($session, $this->customerFactory());
        $context->capture();

        try {
            $context->run(function () {
                throw new \RuntimeException('Clerk down');
            });
            $this->fail('the exception should reach the caller');
        } catch (\RuntimeException $e) {
        }

        $this->assertSame(7, $session->getCustomerId());
        $this->assertSame(5, $session->getCustomerGroupId());
    }

    public function testGuestSessionIsLeftAlone(): void
    {
        $session = $this->session(null, []);
        $context = new GuestCustomerContext($session, $this->customerFactory());
        $context->capture();

        $this->assertSame('ran', $context->run(function () {
            return 'ran';
        }));
        $this->assertSame(0, $session->setCustomerDataCalls);
    }

    /**
     * Mirrors the Magento 2.4.8 getters that catalog price rules and tax read.
     */
    private function session(?CustomerInterface $customer, array $data): Session
    {
        return new class ($customer, $data) extends Session {
            public int $setCustomerDataCalls = 0;

            public function __construct(private ?CustomerInterface $customer, private array $data)
            {
            }

            public function getCustomerId()
            {
                return $this->data['customer_id'] ?? null;
            }

            public function getCustomerGroupId()
            {
                if (!empty($this->data['customer_group_id'])) {
                    return $this->data['customer_group_id'];
                }
                if ($this->getCustomerData()) {
                    $this->data['customer_group_id'] = $this->getCustomerData()->getGroupId();
                    return $this->data['customer_group_id'];
                }
                return 0;
            }

            public function getCustomerData()
            {
                return $this->customer;
            }

            public function setCustomerData(CustomerInterface $customer)
            {
                $this->setCustomerDataCalls++;
                $this->customer = $customer;
                $this->data['customer_id'] = $customer->getId();
                return $this;
            }

            public function getDefaultTaxShippingAddress()
            {
                return $this->data['default_tax_shipping_address'] ?? null;
            }

            public function getDefaultTaxBillingAddress()
            {
                return $this->data['default_tax_billing_address'] ?? null;
            }

            public function getData($key)
            {
                return $this->data[$key] ?? null;
            }

            public function setData($key, $value)
            {
                $this->data[$key] = $value;
                return $this;
            }

            public function unsetData($key)
            {
                unset($this->data[$key]);
                return $this;
            }
        };
    }

    private function customer(?int $id, ?int $groupId): CustomerInterface
    {
        return new class ($id, $groupId) implements CustomerInterface {
            public function __construct(private ?int $id, private ?int $groupId)
            {
            }

            public function getId()
            {
                return $this->id;
            }

            public function getGroupId()
            {
                return $this->groupId;
            }

            public function setGroupId($groupId)
            {
                $this->groupId = $groupId;
                return $this;
            }
        };
    }

    private function customerFactory(): CustomerInterfaceFactory
    {
        $customer = fn () => $this->customer(null, null);
        return new class ($customer) extends CustomerInterfaceFactory {
            public function __construct(private \Closure $customer)
            {
            }

            public function create()
            {
                return ($this->customer)();
            }
        };
    }
}
