<?php

namespace Clerk\Clerk\Controller\Product;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\Handler\Image;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
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
        LoggerInterface $logger
    )
    {
        $this->collectionFactory = $productCollectionFactory;
        $this->imageHandler = $imageHandler;
        $this->addFieldHandlers();

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

        //Filter on is_saleable if defined
        if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY)) {
            $collection->addFieldToFilter('is_saleable', true);
        }

        $visibility = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY);

        switch ($visibility) {
            case Visibility::VISIBILITY_IN_CATALOG:
                $collection->setVisibility(Visibility::VISIBILITY_IN_CATALOG);
                break;
            case Visibility::VISIBILITY_IN_SEARCH:
                $collection->setVisibility(Visibility::VISIBILITY_IN_SEARCH);
                break;
            case Visibility::VISIBILITY_BOTH:
                $collection->setVisibility(Visibility::VISIBILITY_BOTH);
                break;
        }

//
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
