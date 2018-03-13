<?php

namespace Clerk\Clerk\Plugin\Catalog;

use Magento\Framework\Indexer\IndexerRegistry;

class Product
{
    /**
     * @var \Magento\Framework\Indexer\IndexerInterface
     */
    protected $indexer;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexer = $indexerRegistry->get('clerk_products');
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Closure $proceed
     * @param \Magento\Framework\Model\AbstractModel $product
     * @return mixed
     */
    public function aroundSave(
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Closure $proceed,
        \Magento\Framework\Model\AbstractModel $product
    ) {
        $productResource->addCommitCallback(function () use ($product) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($product->getId());
            }
        });

        return $proceed($product);
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Closure $proceed
     * @param \Magento\Framework\Model\AbstractModel $product
     * @return mixed
     */
    public function aroundDelete(
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Closure $proceed,
        \Magento\Framework\Model\AbstractModel $product
    ) {
        $productResource->addCommitCallback(function () use ($product) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($product->getId());
            }
        });

        return $proceed($product);
    }
}
