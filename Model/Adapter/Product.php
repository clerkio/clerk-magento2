<?php

namespace Clerk\Clerk\Model\Adapter;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Helper\Image;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Bundle\Model\Product\Type as Bundle;

class Product extends AbstractAdapter
{
    /**
     * @var LoggerInterface
     */
    protected $clerk_logger;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var string
     */
    protected $eventPrefix = 'product';
    /**
     * @var
     */
    protected $_stockFilter;

    /**
     * @var array
     */
    protected $fieldMap = [
        'entity_id' => 'id',
    ];

    /**
     * Product constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Image $imageHelper,
        ClerkLogger $Clerklogger,
        \Magento\CatalogInventory\Helper\Stock $stockFilter

    )
    {
        $this->_stockFilter = $stockFilter;
        $this->clerk_logger = $Clerklogger;
        $this->imageHelper = $imageHelper;
        parent::__construct($scopeConfig, $eventManager, $storeManager, $collectionFactory, $Clerklogger);
    }

    /**
     * Prepare collection
     *
     * @return mixed
     */
    protected function prepareCollection($page, $limit, $orderBy, $order)
    {
        try {

            $collection = $this->collectionFactory->create();

            $collection->addFieldToSelect('*');

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $version = $productMetadata->getVersion();

            if (!$version >= '2.3.3') {

                //Filter on is_saleable if defined
                if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, ScopeInterface::SCOPE_STORE)) {
                    $this->_stockFilter->addInStockFilterToCollection($collection);
                }


            } else {

                if (!$this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, ScopeInterface::SCOPE_STORE)) {
                    $collection->setFlag('has_stock_status_filter', true);
                }

            }

            $visibility = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY, ScopeInterface::SCOPE_STORE);

            switch ($visibility) {
                case Visibility::VISIBILITY_IN_CATALOG:
                    $collection->setVisibility([Visibility::VISIBILITY_IN_CATALOG]);
                    break;
                case Visibility::VISIBILITY_IN_SEARCH:
                    $collection->setVisibility([Visibility::VISIBILITY_IN_SEARCH]);
                    break;
                case Visibility::VISIBILITY_BOTH:
                    $collection->setVisibility([Visibility::VISIBILITY_BOTH]);
                    break;
            }

            $collection->setPageSize($limit)
                ->setCurPage($page)
                ->addOrder($orderBy, $order);

            $this->eventManager->dispatch('clerk_' . $this->eventPrefix . '_get_collection_after', [
                'adapter' => $this,
                'collection' => $collection
            ]);

            return $collection;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Prepare Collection Error', ['error' => $e->getMessage()]);

        }
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
        try {

            $attribute = $resourceItem->getResource()->getAttribute($field);

            if ($attribute->usesSource()) {
                return $attribute->getSource()->getOptionText($resourceItem[$field]);
            }

            return parent::getAttributeValue($resourceItem, $field);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Attribute Value Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Add field handlers for products
     */
    protected function addFieldHandlers()
    {

        try {

            //Add price fieldhandler
            $this->addFieldHandler('price', function ($item) {
                try {
                    if ($item->getTypeId() === Bundle::TYPE_CODE) {
                        //Get minimum price for bundle products
                        $price = $item
                            ->getPriceInfo()
                            ->getPrice('final_price')
                            ->getMinimalPrice()
                            ->getValue();
                    } else {
                        $price = $item->getFinalPrice();
                    }

                    return number_format( (float)$price, 2 );
                } catch (\Exception $e) {
                    return 0;
                }
            });

            //Add list_price fieldhandler
            $this->addFieldHandler('list_price', function ($item) {
                try {
                    $price = $item->getPrice();

                    //Fix for configurable products
                    if ($item->getTypeId() === Configurable::TYPE_CODE) {
                        $price = $item->getPriceInfo()->getPrice('regular_price')->getValue();
                    }

                    if ($item->getTypeId() === Bundle::TYPE_CODE) {
                        $price = $item
                            ->getPriceInfo()
                            ->getPrice('regular_price')
                            ->getMinimalPrice()
                            ->getValue();
                    }

                    return number_format( (float)$price, 2);
                } catch (\Exception $e) {
                    return 0;
                }
            });

            //Add image fieldhandler
            $this->addFieldHandler('image', function ($item) {
                $imageUrl = $this->imageHelper->getUrl($item);

                return $imageUrl;
            });

            //Add url fieldhandler
            $this->addFieldHandler('url', function ($item) {
                return $item->getUrlModel()->getUrl($item);
            });

            //Add categories fieldhandler
            $this->addFieldHandler('categories', function ($item) {
                return $item->getCategoryIds();
            });

            //Add age fieldhandler
            $this->addFieldHandler('age', function ($item) {
                $createdAt = strtotime($item->getCreatedAt());
                $now = time();
                $diff = $now - $createdAt;
                return floor($diff / (60 * 60 * 24));
            });

            //Add on_sale fieldhandler
            $this->addFieldHandler('on_sale', function ($item) {
                try {
                    $finalPrice = $item->getFinalPrice();
                    $price = $item->getPrice();

                    return $finalPrice < $price;
                } catch (\Exception $e) {
                    return false;
                }
            });

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Field Handlers Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get default product fields
     *
     * @return array
     */
    protected function getDefaultFields()
    {

        try {

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

            $additionalFields = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS, ScopeInterface::SCOPE_STORE);

            if ($additionalFields) {
                $fields = array_merge($fields, str_replace(' ','' ,explode(',', $additionalFields)));
            }

            foreach ($fields as $key => $field) {

                $fields[$key] = $field;

            }

            return $fields;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Default Fields Error', ['error' => $e->getMessage()]);

        }
    }
}
