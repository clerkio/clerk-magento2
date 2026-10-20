<?php

namespace Clerk\Clerk\Test\Unit\Observer;

use Clerk\Clerk\Model\DeferredProductSync;
use Clerk\Clerk\Observer\QuoteSubmitSuccessObserver;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class QuoteSubmitSuccessObserverTest extends TestCase
{
    public function testQueuesOrderedProductsInsteadOfSyncingInline(): void
    {
        $deferred = new class extends DeferredProductSync {
            public array $added = [];

            public function __construct()
            {
            }

            public function add(array $productIds, $storeId)
            {
                $this->added[] = [$productIds, $storeId];
            }
        };

        $item = fn ($productId) => new class ($productId) {
            public function __construct(private $productId)
            {
            }

            public function getProductId()
            {
                return $this->productId;
            }
        };
        $order = new class ([$item(10), $item(null), $item(11)]) {
            public function __construct(private array $items)
            {
            }

            public function getAllItems(): array
            {
                return $this->items;
            }

            public function getStoreId(): int
            {
                return 3;
            }
        };
        $event = new class ($order) {
            public function __construct(private object $order)
            {
            }

            public function getOrder(): object
            {
                return $this->order;
            }
        };

        (new QuoteSubmitSuccessObserver($deferred))->execute(new Observer($event));

        $this->assertSame([[[10, 11], 3]], $deferred->added);
    }
}
