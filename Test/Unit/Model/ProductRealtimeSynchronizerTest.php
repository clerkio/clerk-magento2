<?php

namespace Clerk\Clerk\Test\Unit\Model;

use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\ProductRealtimeSynchronizer;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ProductRealtimeSynchronizerTest extends TestCase
{
    private const NOT_VISIBLE = 1;
    private const VISIBLE_BOTH = 4;

    public function testSoldOutProductIsRemovedNotLeftInClerk(): void
    {
        [$sync, $api] = $this->synchronizer([10 => $this->product(10, false)]);

        $sync->syncProductIds([10], 1);

        $this->assertSame([['remove', 10]], $api->calls);
    }

    public function testSalableProductIsAdded(): void
    {
        [$sync, $api] = $this->synchronizer([10 => $this->product(10, true)]);

        $sync->syncProductIds([10], 1);

        $this->assertSame([['add', 10]], $api->calls);
    }

    public function testEditingAHiddenChildStillResyncsItsParent(): void
    {
        [$sync, $api] = $this->synchronizer(
            [
                11 => $this->product(11, true, self::NOT_VISIBLE),
                20 => $this->product(20, true),
            ],
            [11 => [20]]
        );

        $sync->syncProductIds([11], 1);

        $this->assertSame([['add', 20]], $api->calls);
    }

    public function testDisabledChildIsRemovedAndItsParentResynced(): void
    {
        [$sync, $api] = $this->synchronizer(
            [
                11 => $this->product(11, true, self::VISIBLE_BOTH, Status::STATUS_DISABLED),
                20 => $this->product(20, true),
            ],
            [11 => [20]]
        );

        $sync->syncProductIds([11], 1);

        $this->assertSame([['add', 20], ['remove', 11]], $api->calls);
    }

    public function testSoldOutParentIsRemoved(): void
    {
        [$sync, $api] = $this->synchronizer(
            [
                11 => $this->product(11, false, self::NOT_VISIBLE),
                20 => $this->product(20, false),
            ],
            [11 => [20]]
        );

        $sync->syncProductIds([11], 1);

        $this->assertSame([['remove', 20]], $api->calls);
    }

    public function testOrderSyncLoadsFreshProducts(): void
    {
        [$sync, , $repository] = $this->synchronizer([10 => $this->product(10, true)]);

        $sync->syncProductIds([10], 1);

        $this->assertSame([[10, 1, true]], $repository->loads);
    }

    /**
     * @param array<int, object> $products
     * @param array<int, int[]> $parents child ID => configurable parent IDs
     */
    private function synchronizer(array $products, array $parents = []): array
    {
        $api = new class {
            public array $calls = [];

            public function addProduct($params, $storeId)
            {
                $this->calls[] = ['add', $params['id']];
            }

            public function removeProduct($productId, $storeId)
            {
                $this->calls[] = ['remove', $productId];
            }
        };
        $repository = new class ($products) {
            public array $loads = [];

            public function __construct(private array $products)
            {
            }

            public function getById($id, $editMode = false, $storeId = null, $forceReload = false)
            {
                $this->loads[] = [$id, $storeId, $forceReload];
                return $this->products[$id];
            }
        };
        $config = new class {
            public function getValue($path, $scope = null, $scopeId = null)
            {
                return [
                    Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED => '1',
                    Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY => '4',
                    Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY => '1',
                ][$path] ?? null;
            }
        };
        $configurable = new class ($parents) {
            public function __construct(private array $parents)
            {
            }

            public function getParentIdsByChild($childId)
            {
                return $this->parents[$childId] ?? [];
            }
        };
        $grouped = new class {
            public function getParentIdsByChild($childId)
            {
                return [];
            }
        };
        $adapter = new class {
            public function getInfoForItem($product, $scope, $scopeId)
            {
                return ['id' => $product->getId()];
            }
        };
        $emulation = new class {
            public function startEnvironmentEmulation($storeId)
            {
            }

            public function stopEnvironmentEmulation()
            {
            }
        };

        $sync = (new ReflectionClass(ProductRealtimeSynchronizer::class))->newInstanceWithoutConstructor();
        $this->setProperty($sync, 'api', $api);
        $this->setProperty($sync, 'productRepository', $repository);
        $this->setProperty($sync, 'scopeConfig', $config);
        $this->setProperty($sync, '_productModelConfigurable', $configurable);
        $this->setProperty($sync, '_productModelGrouped', $grouped);
        $this->setProperty($sync, 'productAdapter', $adapter);
        $this->setProperty($sync, 'emulation', $emulation);

        return [$sync, $api, $repository];
    }

    private function product(int $id, bool $salable, int $visibility = self::VISIBLE_BOTH, int $status = Status::STATUS_ENABLED): object
    {
        return new class ($id, $salable, $visibility, $status) {
            public function __construct(private int $id, private bool $salable, private int $visibility, private int $status)
            {
            }

            public function getId()
            {
                return $this->id;
            }

            public function isSalable()
            {
                return $this->salable;
            }

            public function getVisibility()
            {
                return $this->visibility;
            }

            public function getStatus()
            {
                return $this->status;
            }
        };
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $prop = (new ReflectionClass($object))->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
