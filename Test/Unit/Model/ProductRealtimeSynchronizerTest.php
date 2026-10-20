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

    public function testLastUnitSoldIsRemovedEvenWhenTheStockIndexIsStale(): void
    {
        // "Update by Schedule": the index still says salable, the stock item says out of stock.
        [$sync, $api] = $this->synchronizer([10 => $this->product(10, true)], [10 => false]);

        $sync->syncProductIds([10], 1);

        $this->assertSame([['remove', 10]], $api->calls);
    }

    public function testRestockedProductIsAddedEvenWhenTheStockIndexIsStale(): void
    {
        [$sync, $api] = $this->synchronizer([10 => $this->product(10, false)], [10 => true]);

        $sync->syncProduct($this->product(10, false), 1);

        $this->assertSame([['add', 10]], $api->calls);
    }

    public function testProductWithoutManagedStockFallsBackToSalability(): void
    {
        [$sync, $api] = $this->synchronizer([10 => $this->product(10, false)]);

        $sync->syncProductIds([10], 1);

        $this->assertSame([['remove', 10]], $api->calls);
    }

    public function testStockCacheIsClearedBeforeEveryLoad(): void
    {
        [$sync, , , $log] = $this->synchronizer([10 => $this->product(10, true)], [10 => true]);

        $sync->syncProduct($this->product(10, true), 1);

        $this->assertSame(['clear stock item 10', 'clear stock status 10', 'load 10 store 1 forced'], $log->entries);
    }

    public function testEditingAHiddenChildStillResyncsItsParent(): void
    {
        [$sync, $api] = $this->synchronizer(
            [
                11 => $this->product(11, true, self::NOT_VISIBLE),
                20 => $this->product(20, true, self::VISIBLE_BOTH, Status::STATUS_ENABLED, true),
            ],
            [11 => true],
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
                20 => $this->product(20, true, self::VISIBLE_BOTH, Status::STATUS_ENABLED, true),
            ],
            [11 => true],
            [11 => [20]]
        );

        $sync->syncProductIds([11], 1);

        $this->assertSame([['add', 20], ['remove', 11]], $api->calls);
    }

    public function testSoldOutConfigurableParentIsRemoved(): void
    {
        [$sync, $api] = $this->synchronizer(
            [
                11 => $this->product(11, false, self::NOT_VISIBLE),
                20 => $this->product(20, false, self::VISIBLE_BOTH, Status::STATUS_ENABLED, true),
            ],
            [11 => false, 20 => true],
            [11 => [20]]
        );

        $sync->syncProductIds([11], 1);

        $this->assertSame([['remove', 20]], $api->calls, 'composite products ignore their own stock flag');
    }

    /**
     * @param array<int, object> $products
     * @param array<int, bool> $stock product ID => is_in_stock of a managed stock item; missing = no managed stock
     * @param array<int, int[]> $parents child ID => configurable parent IDs
     */
    private function synchronizer(array $products, array $stock = [], array $parents = []): array
    {
        $log = new class {
            public array $entries = [];
        };
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
        $repository = new class ($products, $log) {
            public function __construct(private array $products, private object $log)
            {
            }

            public function getById($id, $editMode = false, $storeId = null, $forceReload = false)
            {
                $this->log->entries[] = "load $id store $storeId" . ($forceReload ? ' forced' : '');
                return $this->products[$id];
            }
        };
        $storage = new class ($log) {
            public function __construct(private object $log)
            {
            }

            public function removeStockItem($productId, $scopeId = null)
            {
                $this->log->entries[] = "clear stock item $productId";
            }

            public function removeStockStatus($productId, $scopeId = null)
            {
                $this->log->entries[] = "clear stock status $productId";
            }
        };
        $registry = new class ($stock) {
            public function __construct(private array $stock)
            {
            }

            public function getStockItem($productId, $scopeId = null)
            {
                $managed = array_key_exists($productId, $this->stock);
                return new class ($managed, $this->stock[$productId] ?? false) {
                    public function __construct(private bool $managed, private bool $inStock)
                    {
                    }

                    public function getItemId()
                    {
                        return $this->managed ? 1 : null;
                    }

                    public function getManageStock()
                    {
                        return $this->managed;
                    }

                    public function getIsInStock()
                    {
                        return $this->inStock;
                    }
                };
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
        $this->setProperty($sync, 'stockRegistryStorage', $storage);
        $this->setProperty($sync, 'stockRegistry', $registry);
        $this->setProperty($sync, 'scopeConfig', $config);
        $this->setProperty($sync, '_productModelConfigurable', $configurable);
        $this->setProperty($sync, '_productModelGrouped', $grouped);
        $this->setProperty($sync, 'productAdapter', $adapter);
        $this->setProperty($sync, 'emulation', $emulation);

        return [$sync, $api, $repository, $log];
    }

    /**
     * @param bool $salable what Product::isSalable() returns, i.e. the stock index
     */
    private function product(
        int $id,
        bool $salable,
        int $visibility = self::VISIBLE_BOTH,
        int $status = Status::STATUS_ENABLED,
        bool $composite = false
    ): object {
        return new class ($id, $salable, $visibility, $status, $composite) {
            public function __construct(
                private int $id,
                private bool $salable,
                private int $visibility,
                private int $status,
                private bool $composite
            ) {
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

            public function getTypeInstance()
            {
                $composite = $this->composite;
                return new class ($composite) {
                    public function __construct(private bool $composite)
                    {
                    }

                    public function isComposite($product)
                    {
                        return $this->composite;
                    }
                };
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
