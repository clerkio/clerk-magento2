<?php

namespace Clerk\Clerk\Model;

use Psr\Log\LoggerInterface;

/**
 * Runs order and credit memo product syncs after the response is sent, so a slow Clerk API never blocks checkout.
 */
class DeferredProductSync
{
    /**
     * @var ProductRealtimeSynchronizer
     */
    protected $productRealtimeSynchronizer;

    /**
     * @var GuestCustomerContext
     */
    protected $guestCustomerContext;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Product IDs waiting to be synced, keyed by store ID.
     *
     * @var array
     */
    protected $pending = [];

    /**
     * @var bool
     */
    protected $shutdownRegistered = false;

    public function __construct(
        ProductRealtimeSynchronizer $productRealtimeSynchronizer,
        GuestCustomerContext $guestCustomerContext,
        LoggerInterface $logger
    ) {
        $this->productRealtimeSynchronizer = $productRealtimeSynchronizer;
        $this->guestCustomerContext = $guestCustomerContext;
        $this->logger = $logger;
    }

    /**
     * Queue products for sync at the end of the request.
     *
     * @param int[] $productIds
     * @param int|string $storeId
     * @return void
     */
    public function add(array $productIds, $storeId)
    {
        // Cron and CLI have nobody waiting on a response, and long-running consumers may never shut down.
        if ($this->isCli()) {
            $this->sync($productIds, $storeId);
            return;
        }

        try {
            $this->guestCustomerContext->capture();
        } catch (\Throwable $e) {
            // This runs inside checkout, so it must never throw.
            $this->logger->error('Clerk could not read the customer session: ' . $e->getMessage());
        }
        foreach ($productIds as $productId) {
            $this->pending[$storeId][] = $productId;
        }

        if (!$this->shutdownRegistered) {
            $this->shutdownRegistered = true;
            $this->registerShutdown();
        }
    }

    /**
     * Send the response to the customer, then sync the queued products.
     *
     * @return void
     */
    public function flush()
    {
        $pending = $this->pending;
        $this->pending = [];
        if (empty($pending)) {
            return;
        }

        // Release the session lock first, or the checkout success page waits for the sync anyway.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Prices are computed during the sync, and must be the catalogue's, not the buyer's.
        try {
            $this->guestCustomerContext->run(function () use ($pending) {
                foreach ($pending as $storeId => $productIds) {
                    $this->sync($productIds, $storeId);
                }
            });
        } catch (\Throwable $e) {
            $this->logger->error('Clerk realtime product sync failed: ' . $e->getMessage());
        }
    }

    /**
     * @param int[] $productIds
     * @param int|string $storeId
     * @return void
     */
    protected function sync(array $productIds, $storeId)
    {
        try {
            $this->productRealtimeSynchronizer->syncProductIds($productIds, $storeId);
        } catch (\Throwable $e) {
            // Nothing upstream catches errors this late, so log instead of letting them surface.
            $this->logger->error('Clerk realtime product sync failed: ' . $e->getMessage());
        }
    }

    /**
     * @return bool
     */
    protected function isCli()
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * @return void
     */
    protected function registerShutdown()
    {
        register_shutdown_function([$this, 'flush']);
    }
}
