<?php

namespace Clerk\Clerk\Observer;

use Clerk\Clerk\Model\ProductRealtimeSynchronizer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfterObserver implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ProductRealtimeSynchronizer
     */
    protected $productRealtimeSynchronizer;

    public function __construct(
        RequestInterface $request,
        ProductRealtimeSynchronizer $productRealtimeSynchronizer
    ) {
        $this->request = $request;
        $this->productRealtimeSynchronizer = $productRealtimeSynchronizer;
    }

    /**
     * Add product to Clerk
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if (!$product) {
            return;
        }

        $params = $this->request->getParams();
        $storeId = 0;
        if (array_key_exists('store', $params)) {
            $storeId = $params['store'];
        }

        $this->productRealtimeSynchronizer->syncProduct($product, $storeId);
    }
}
