<?php

namespace Clerk\Clerk\Controller\Resource;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Psr\Log\LoggerInterface;

class Category extends AbstractAction
{
    protected $fieldMap = [
        'entity_id' => 'id',
        'parent_id' => 'parent',
    ];

    /**
     * Category controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, CollectionFactory $categoryCollectionFactory, LoggerInterface $logger)
    {
        $this->collectionFactory = $categoryCollectionFactory;

        $this->addFieldHandlers();

        parent::__construct($context, $scopeConfig, $logger);
    }

    /**
     * Add field handlers
     */
    protected function addFieldHandlers()
    {
        //Add parent fieldhandler
        $this->addFieldHandler('parent', function($item) {
            return $item->getParentId();
        });

        //Add url fieldhandler
        $this->addFieldHandler('url', function($item) {
            return $item->getUrl();
        });

        //Add subcategories fieldhandler
        $this->addFieldHandler('subcategories', function($item) {
            $children = $item->getAllChildren(true);
            //Remove own ID from subcategories array
            return array_values(array_diff($children, [$item->getId()]));
        });
    }
}