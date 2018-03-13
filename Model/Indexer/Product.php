<?php

namespace Clerk\Clerk\Model\Indexer;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var Product\Action\Rows
     */
    protected $productIndexerRows;

    /**
     * @param Product\Action\Rows $productIndexerRows
     */
    public function __construct(
        Product\Action\Rows $productIndexerRows
    ) {
        $this->productIndexerRows = $productIndexerRows;
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
        // not implemented
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
