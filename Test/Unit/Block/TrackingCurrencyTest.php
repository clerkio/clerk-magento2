<?php

namespace Clerk\Clerk\Test\Unit\Block;

use Clerk\Clerk\Block\Tracking;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TrackingCurrencyTest extends TestCase
{
    public function testSymbolAndRateMapsUseAllowedCurrenciesOnly(): void
    {
        $installed = [];
        for ($i = 0; $i < 120; $i++) {
            $installed[] = sprintf('C%03d', $i);
        }
        $allowed = ['EUR', 'DKK'];
        $store = new class ($installed, $allowed) {
            public int $allowedLookups = 0;
            public int $availableLookups = 0;
            public ?bool $skipBaseNotAllowed = null;

            public function __construct(private array $installed, private array $allowed)
            {
            }

            public function getAllowedCurrencies(): array
            {
                $this->allowedLookups++;
                return $this->installed;
            }

            public function getAvailableCurrencyCodes(bool $skipBaseNotAllowed = false): array
            {
                $this->availableLookups++;
                $this->skipBaseNotAllowed = $skipBaseNotAllowed;
                return $this->allowed;
            }

            public function getBaseCurrency(): object
            {
                return new class {
                    public function getRate(string $currencyIso): float
                    {
                        return $currencyIso === 'EUR' ? 1.0 : 7.46;
                    }
                };
            }
        };
        $localeCurrency = new class {
            public array $requested = [];

            public function getCurrency(string $code): object
            {
                $this->requested[] = $code;
                return new class ($code) {
                    public function __construct(private string $code)
                    {
                    }

                    public function getSymbol(): string
                    {
                        return $this->code === 'EUR' ? '€' : 'kr.';
                    }
                };
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

        $block = (new ReflectionClass(Tracking::class))->newInstanceWithoutConstructor();
        $this->setProperty($block, '_storeManager', $storeManager);
        $this->setProperty($block, '_localeCurrency', $localeCurrency);

        $this->assertSame(['EUR' => '€', 'DKK' => 'kr.'], $block->getAllCurrencySymbols());
        $this->assertSame(['EUR' => 1.0, 'DKK' => 7.46], $block->getAllCurrencyRates());
        $this->assertSame(['EUR', 'DKK'], $localeCurrency->requested);
        $this->assertSame(0, $store->allowedLookups);
        $this->assertSame(2, $store->availableLookups);
        $this->assertTrue($store->skipBaseNotAllowed);
        $this->assertSame($installed, $block->getAllowedCurrencies());
        $this->assertSame(1, $store->allowedLookups);
    }

    public function testMissingCurrencyIsoRateStaysOne(): void
    {
        $block = (new ReflectionClass(Tracking::class))->newInstanceWithoutConstructor();

        $this->assertSame(1.0, $block->getCurrencyRateFromIso(null));
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $prop = (new ReflectionClass($object))->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
