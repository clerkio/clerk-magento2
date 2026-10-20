<?php

namespace Clerk\Clerk\Test\Unit\Model;

use Clerk\Clerk\Model\DeferredProductSync;
use Clerk\Clerk\Model\GuestCustomerContext;
use Clerk\Clerk\Model\ProductRealtimeSynchronizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeferredProductSyncTest extends TestCase
{
    public function testWebRequestWaitsForShutdownAndSyncsOncePerStore(): void
    {
        $synchronizer = $this->synchronizer();
        $sync = $this->deferredSync($synchronizer, $this->logger(), false);

        $sync->add([10, 11], 1);
        $sync->add([12], 1);
        $sync->add([20], 2);

        $this->assertSame([], $synchronizer->calls, 'nothing is sent to Clerk during the request');
        $this->assertSame(1, $sync->shutdownRegistrations);

        $sync->flush();
        $sync->flush();

        $this->assertSame([
            [[10, 11, 12], 1],
            [[20], 2],
        ], $synchronizer->calls);
    }

    public function testCliSyncsImmediately(): void
    {
        $synchronizer = $this->synchronizer();
        $sync = $this->deferredSync($synchronizer, $this->logger(), true);

        $sync->add([10], 1);

        $this->assertSame([[[10], 1]], $synchronizer->calls);
        $this->assertSame(0, $sync->shutdownRegistrations);
    }

    public function testErrorsAreLoggedNotThrown(): void
    {
        $synchronizer = $this->synchronizer(new \TypeError('boom'));
        $logger = $this->logger();
        $sync = $this->deferredSync($synchronizer, $logger, false);

        $sync->add([10], 1);
        $sync->flush();

        $this->assertSame(['Clerk realtime product sync failed: boom'], $logger->errors);
    }

    public function testDeferredSyncRunsAsAGuest(): void
    {
        $guest = $this->guestContext();
        $synchronizer = $this->synchronizer(null, $guest);
        $sync = $this->deferredSync($synchronizer, $this->logger(), false, $guest);

        $sync->add([10], 1);
        $this->assertSame(1, $guest->captures, 'the buyer is captured during the request');

        $sync->flush();
        $this->assertSame([true], $synchronizer->ranAsGuest);
    }

    public function testCustomerSessionFailureDoesNotBreakCheckout(): void
    {
        $guest = $this->guestContext(new \RuntimeException('Area code not set'));
        $synchronizer = $this->synchronizer();
        $logger = $this->logger();
        $sync = $this->deferredSync($synchronizer, $logger, false, $guest);

        $sync->add([10], 1);
        $sync->flush();

        $this->assertSame(['Clerk could not read the customer session: Area code not set'], $logger->errors);
        $this->assertSame([[[10], 1]], $synchronizer->calls);
    }

    private function deferredSync(
        ProductRealtimeSynchronizer $synchronizer,
        LoggerInterface $logger,
        bool $cli,
        ?GuestCustomerContext $guest = null
    ): DeferredProductSync {
        return new class ($synchronizer, $guest ?? $this->guestContext(), $logger, $cli) extends DeferredProductSync {
            public int $shutdownRegistrations = 0;

            public function __construct(
                ProductRealtimeSynchronizer $synchronizer,
                GuestCustomerContext $guest,
                LoggerInterface $logger,
                private bool $cli
            ) {
                parent::__construct($synchronizer, $guest, $logger);
            }

            protected function isCli()
            {
                return $this->cli;
            }

            protected function registerShutdown()
            {
                $this->shutdownRegistrations++;
            }
        };
    }

    private function synchronizer(?\Throwable $error = null, ?object $guest = null): ProductRealtimeSynchronizer
    {
        return new class ($error, $guest) extends ProductRealtimeSynchronizer {
            public array $calls = [];
            public array $ranAsGuest = [];

            public function __construct(private ?\Throwable $error, private ?object $guest)
            {
            }

            public function syncProductIds(array $productIds, $storeId)
            {
                if ($this->error) {
                    throw $this->error;
                }
                $this->calls[] = [$productIds, $storeId];
                if ($this->guest) {
                    $this->ranAsGuest[] = $this->guest->active;
                }
            }
        };
    }

    private function guestContext(?\Throwable $captureError = null): GuestCustomerContext
    {
        return new class ($captureError) extends GuestCustomerContext {
            public int $captures = 0;
            public bool $active = false;

            public function __construct(private ?\Throwable $captureError)
            {
            }

            public function capture()
            {
                $this->captures++;
                if ($this->captureError) {
                    throw $this->captureError;
                }
            }

            public function run(callable $callback)
            {
                $this->active = true;
                try {
                    return $callback();
                } finally {
                    $this->active = false;
                }
            }
        };
    }

    private function logger(): LoggerInterface
    {
        return new class implements LoggerInterface {
            public array $errors = [];

            public function error($message, array $context = [])
            {
                $this->errors[] = $message;
            }
        };
    }
}
