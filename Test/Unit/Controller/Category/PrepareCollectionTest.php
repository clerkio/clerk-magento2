<?php

namespace Clerk\Clerk\Test\Unit\Controller\Category;

use Clerk\Clerk\Controller\Category\Index;
use Clerk\Clerk\Test\Unit\RecordingCollection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class PrepareCollectionTest extends TestCase
{
    public function testPagesCategoriesInStableEntityIdOrder(): void
    {
        $collection = new RecordingCollection();
        $controller = $this->controller($collection, 5, 2, 100);

        $result = $this->prepare($controller);

        $this->assertSame($collection, $result);
        $this->assertSame([
            ['addFieldToSelect', ['*']],
            ['addAttributeToFilter', ['level', ['gteq' => 2]]],
            ['addAttributeToFilter', ['name', ['neq' => null]]],
            ['addPathsFilter', ['1/5/%']],
            ['addFieldToFilter', ['is_active', ['in' => ['1']]]],
            ['setOrder', ['entity_id', 'ASC']],
            ['setCurPage', [2]],
            ['setPageSize', [100]],
        ], $collection->calls);
    }

    public function testPrepareCollectionStillLogsAndReturnsNullOnFailure(): void
    {
        $factory = new class {
            public function create(): void
            {
                throw new \RuntimeException('db down');
            }
        };
        $logger = new class {
            public array $logged = [];

            public function error(string $message, array $context): void
            {
                $this->logged = [$message, $context];
            }
        };
        $controller = (new ReflectionClass(Index::class))->newInstanceWithoutConstructor();
        $this->setProperty($controller, 'collectionFactory', $factory);
        $this->setProperty($controller, 'clerk_logger', $logger);

        $result = $this->prepare($controller);

        $this->assertNull($result);
        $this->assertSame(['Category prepareCollection ERROR', ['error' => 'db down']], $logger->logged);
    }

    private function controller(RecordingCollection $collection, int $rootId, int $page, int $limit): Index
    {
        $store = new class ($rootId) {
            public function __construct(private int $rootId)
            {
            }

            public function getRootCategoryId(): int
            {
                return $this->rootId;
            }
        };
        $storeManager = new class ($store) {
            public function __construct(private object $store)
            {
            }

            public function getStore(): object
            {
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
        $this->setProperty($controller, 'collectionFactory', $factory);
        $this->setProperty($controller, 'storeManager', $storeManager);
        $this->setProperty($controller, 'page', $page);
        $this->setProperty($controller, 'limit', $limit);

        return $controller;
    }

    private function prepare(Index $controller): mixed
    {
        $method = new ReflectionMethod(Index::class, 'prepareCollection');
        $method->setAccessible(true);

        return $method->invoke($controller);
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        while ($reflection !== false && !$reflection->hasProperty($property)) {
            $reflection = $reflection->getParentClass();
        }
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
