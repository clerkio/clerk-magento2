<?php

namespace Clerk\Clerk\Test\Unit\Model\Adapter;

use Clerk\Clerk\Model\Adapter\Product;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ProductUrlTest extends TestCase
{
    private const SHOP = 'https://shop.test/';
    private const NL = 'https://shop.test/nl/';

    public function testStoreParameterIsDroppedWhenStoreHasItsOwnBaseUrl(): void
    {
        $url = $this->productUrl(
            'https://shop.test/nl/clerk-test-product.html?___store=nl',
            [1 => $this->store(self::SHOP), 2 => $this->store(self::NL)]
        );

        $this->assertSame('https://shop.test/nl/clerk-test-product.html', $url);
    }

    public function testStoreParameterIsKeptWhenBaseUrlIsSharedWithAnotherActiveStore(): void
    {
        $url = $this->productUrl(
            'https://shop.test/clerk-test-product.html?___store=nl',
            [1 => $this->store(self::SHOP), 2 => $this->store(self::SHOP)]
        );

        $this->assertSame('https://shop.test/clerk-test-product.html?___store=nl', $url);
    }

    public function testDisabledStoreSharingTheBaseUrlIsIgnored(): void
    {
        $url = $this->productUrl(
            'https://shop.test/clerk-test-product.html?___store=nl',
            [1 => $this->store(self::SHOP, self::SHOP, false), 2 => $this->store(self::SHOP)]
        );

        $this->assertSame('https://shop.test/clerk-test-product.html', $url);
    }

    public function testStoreParameterIsKeptWhenOnlyTheSecureBaseUrlIsShared(): void
    {
        $url = $this->productUrl(
            'https://shop.test/nl/p.html?___store=nl',
            [1 => $this->store(self::SHOP, 'https://secure.test/'), 2 => $this->store(self::NL, 'https://secure.test/')]
        );

        $this->assertSame('https://shop.test/nl/p.html?___store=nl', $url);
    }

    public function testStoreParameterIsKeptWhenOnlyTheUnsecureBaseUrlIsShared(): void
    {
        $url = $this->productUrl(
            'https://shop.test/nl/p.html?___store=nl',
            [1 => $this->store(self::SHOP, 'https://a.test/'), 2 => $this->store(self::SHOP, 'https://b.test/')]
        );

        $this->assertSame('https://shop.test/nl/p.html?___store=nl', $url);
    }

    public function testOtherQueryParametersSurvive(): void
    {
        $url = $this->productUrl(
            'https://shop.test/nl/p.html?___store=nl&color=red',
            [1 => $this->store(self::SHOP), 2 => $this->store(self::NL)]
        );

        $this->assertSame('https://shop.test/nl/p.html?color=red', $url);
    }

    public function testUrlWithoutStoreParameterIsUnchanged(): void
    {
        $url = $this->productUrl(
            'https://shop.test/nl/p.html',
            [1 => $this->store(self::SHOP), 2 => $this->store(self::NL)]
        );

        $this->assertSame('https://shop.test/nl/p.html', $url);
    }

    private function store(string $unsecure, ?string $secure = null, bool $active = true): array
    {
        return ['unsecure' => $unsecure, 'secure' => $secure ?? $unsecure, 'active' => $active];
    }

    private function productUrl(string $urlInStore, array $definitions): string
    {
        $stores = [];
        foreach ($definitions as $id => $definition) {
            $stores[$id] = new class ($id, $definition) {
                public function __construct(private int $id, private array $definition)
                {
                }

                public function getId(): int
                {
                    return $this->id;
                }

                public function isActive(): bool
                {
                    return $this->definition['active'];
                }

                public function getBaseUrl(string $type, ?bool $secure = null): string
                {
                    return $secure ? $this->definition['secure'] : $this->definition['unsecure'];
                }
            };
        }
        $storeManager = new class ($stores) {
            public function __construct(private array $stores)
            {
            }

            public function getStore($id)
            {
                return $this->stores[$id];
            }

            public function getStores(): array
            {
                return $this->stores;
            }
        };
        $item = new class ($urlInStore) {
            public function __construct(private string $url)
            {
            }

            public function setStoreId($storeId): self
            {
                return $this;
            }

            public function getUrlInStore(): string
            {
                return $this->url;
            }
        };

        $adapter = (new ReflectionClass(Product::class))->newInstanceWithoutConstructor();
        $this->setProperty($adapter, 'storeManager', $storeManager);
        $this->setProperty($adapter, 'scopeId', 2);

        $method = new ReflectionMethod(Product::class, 'getFeedProductUrl');

        return $method->invoke($adapter, $item);
    }

    private function setProperty(object $object, string $name, $value): void
    {
        $property = new ReflectionProperty(Product::class, $name);
        $property->setValue($object, $value);
    }
}
