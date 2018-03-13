<?php

namespace Clerk\Clerk\Plugin\Catalog\Product;

use Magento\Framework\Indexer\IndexerRegistry;

class Action
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
     * @param \Magento\Catalog\Model\Product\Action $subject
     * @param \Closure $closure
     * @param array $productIds
     * @param array $attrData
     * @param $storeId
     * @return mixed
     */
    public function aroundUpdateAttributes(
        \Magento\Catalog\Model\Product\Action $subject,
        \Closure $closure,
        array $productIds,
        array $attrData,
        $storeId
    ) {
        $result = $closure($productIds, $attrData, $storeId);
        if (!$this->indexer->isScheduled()) {
            $this->indexer->reindexList(array_unique($productIds));
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product\Action $subject
     * @param \Closure $closure
     * @param array $productIds
     * @param array $websiteIds
     * @param $type
     * @return mixed
     */
    public function aroundUpdateWebsites(
        \Magento\Catalog\Model\Product\Action $subject,
        \Closure $closure,
        array $productIds,
        array $websiteIds,
        $type
    ) {
        $result = $closure($productIds, $websiteIds, $type);
        if (!$this->indexer->isScheduled()) {
            $this->indexer->reindexList(array_unique($productIds));
        }

        return $result;
    }
}
