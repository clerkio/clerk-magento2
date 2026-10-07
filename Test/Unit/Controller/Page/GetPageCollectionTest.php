<?php

namespace Clerk\Clerk\Test\Unit\Controller\Page;

use Clerk\Clerk\Controller\Page\Index;
use Clerk\Clerk\Test\Unit\RecordingCollection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GetPageCollectionTest extends TestCase
{
    public function testPagesCmsPagesByPageIdWithoutChangingFilters(): void
    {
        $collection = new RecordingCollection();
        $store = new class {
            public function getId(): int
            {
                return 4;
            }
        };
        $storeManager = new class ($store) {
            public mixed $requestedStoreId = null;

            public function __construct(private object $store)
            {
            }

            public function getStore(mixed $storeId): object
            {
                $this->requestedStoreId = $storeId;
                return $this->store;
            }
        };
        $factory = new class ($collection) {
            public function __construct(private RecordingCollection $collection)
            {
            }

            public function create(): RecordingCollection
            {
                return $this->collection;
            }
        };

        $controller = (new ReflectionClass(Index::class))->newInstanceWithoutConstructor();
        $storeManagerProperty = (new ReflectionClass(Index::class))->getProperty('storeManager');
        $storeManagerProperty->setAccessible(true);
        $storeManagerProperty->setValue($controller, $storeManager);
        $factoryProperty = (new ReflectionClass(Index::class))->getProperty('_pageFactory');
        $factoryProperty->setAccessible(true);
        $factoryProperty->setValue($controller, $factory);

        $result = $controller->getPageCollection(3, 50, 4);

        $this->assertSame($collection, $result);
        $this->assertSame(4, $storeManager->requestedStoreId);
        $this->assertSame([
            ['addFilter', ['is_active', 1]],
            ['addFilter', ['store_id', 4]],
            ['addStoreFilter', [$store]],
            ['setOrder', ['main_table.page_id', 'ASC']],
            ['setPageSize', [50]],
            ['setCurPage', [3]],
        ], $collection->calls);
    }
}
