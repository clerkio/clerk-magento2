<?php

namespace Clerk\Clerk\Controller\Product;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\Handler\Image;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Helper\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    /**
     * @var array
     */
    protected $fieldMap = [
        'entity_id' => 'id',
    ];

    /**
     * @var string
     */
    protected $eventPrefix = 'clerk_product';

    /**
     * @var Image
     */
    protected $imageHandler;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var Stock
     */
    protected $_stockFilter;

    /**
     * Popup controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $productCollectionFactory,
        Image $imageHandler,
        LoggerInterface $logger,
        ProductMetadataInterface $productMetadata,
        Stock $stockFilter
    )
    {
        $this->collectionFactory = $productCollectionFactory;
        $this->imageHandler = $imageHandler;
        $this->addFieldHandlers();
        $this->productMetadata = $productMetadata;
        $this->_stockFilter = $stockFilter;

        parent::__construct($context, $scopeConfig, $logger);
    }

    /**
     * Add field handlers
     */
    protected function addFieldHandlers()
    {
        //Add price fieldhandler
        $this->addFieldHandler('price', function($item) {
            try {
                $price = $item->getFinalPrice();
                return (float) $price;
            } catch(\Exception $e) {
                return 0;
            }
        });

        //Add list_price fieldhandler
        $this->addFieldHandler('list_price', function($item) {
            try {
                $price = $item->getPrice();

                //Fix for configurable products
                if ($item->getTypeId() === Configurable::TYPE_CODE) {
                    $price = $item->getPriceInfo()->getPrice('regular_price')->getValue();//->getPrice('regular_price');
                }

                return (float) $price;
            } catch(\Exception $e) {
                return 0;
            }
        });


        //Add image fieldhandler
        $this->addFieldHandler('image', function($item) {
            return $this->imageHandler->handle($item);
        });

        //Add url fieldhandler
        $this->addFieldHandler('url', function($item) {
            return $item->getUrlModel()->getUrl($item);
        });

        //Add categories fieldhandler
        $this->addFieldHandler('categories', function($item) {
            return $item->getCategoryIds();
        });

        //Add age fieldhandler
        $this->addFieldHandler('age', function($item) {
            $createdAt = strtotime($item->getCreatedAt());
            $now = time();
            $diff = $now - $createdAt;
            return floor($diff/(60*60*24));
        });

        //Add on_sale fieldhandler
        $this->addFieldHandler('on_sale', function($item) {
            try {
                $finalPrice = $item->getFinalPrice();
                $price = $item->getPrice();

                return $finalPrice < $price;
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    /**
     * Get default fields
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        $fields = [
            'name',
            'description',
            'price',
            'list_price',
            'image',
            'url',
            'categories',
            'brand',
            'sku',
            'age',
            'on_sale'
        ];

        $additionalFields = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS);

        if ($additionalFields) {
            $fields = array_merge($fields, explode(',', $additionalFields));
        }

        return $fields;
    }

    /**
     * Prepare collection
     *
     * @return mixed
     */
    protected function prepareCollection()
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToSelect('*');

        $version = $this->productMetadata->getVersion();

        if (!$version >= '2.3.3') {
            if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY)) {
                // Filter on is_saleable if defined
                $this->_stockFilter->addInStockFilterToCollection($collection);
            }
        } else {
            if (!$this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY)) {
                // To bypass removing out of stock products in results
                $collection->setFlag('has_stock_status_filter', true);
            }
        }

        $visibility = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY);
        $collection->addFieldToFilter('visibility', $visibility);
        $collection->setPageSize($this->limit)
            ->setCurPage($this->page)
            ->addOrder($this->orderBy, $this->order);

        return $collection;
    }

    /**
     * Get attribute value for product
     *
     * @param $resourceItem
     * @param $field
     * @return mixed
     */
    protected function getAttributeValue($resourceItem, $field)
    {
        $attribute = $resourceItem->getResource()->getAttribute($field);

        if ($attribute->usesSource()) {
            return $attribute->getSource()->getOptionText($resourceItem[$field]);
        }

        return parent::getAttributeValue($resourceItem, $field);
    }
}
