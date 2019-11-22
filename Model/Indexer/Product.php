<?php

namespace Clerk\Clerk\Model\Indexer;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory 
     */
    protected $productCollectionFactory;
    
    /**
     * @var Product\Action\Rows
     */
    protected $productIndexerRows;

    /**
     * @param Product\Action\Rows $productIndexerRows
     */
    public function __construct(
        Product\Action\Rows $productIndexerRows,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {
        $this->productIndexerRows = $productIndexerRows;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function execute($ids)
    {
        $this->productIndexerRows->execute($ids);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $ids = $collection->getAllIds();
        $this->executeList($ids);
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->productIndexerRows->execute($ids);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    public function executeRow($id)
    {
        $this->productIndexerRows->execute([$id]);
    }
}
