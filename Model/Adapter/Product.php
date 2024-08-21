<?php

namespace Clerk\Clerk\Model\Adapter;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Helper\Image;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Helper\Stock as StockFilter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation\RateFactory as TaxRateFactory;

class Product extends AbstractAdapter
{

    public const PRODUCT_TYPE_SIMPLE = 'simple';
    public const PRODUCT_TYPE_CONFIGURABLE = 'configurable';
    public const PRODUCT_TYPE_GROUPED = 'grouped';
    public const PRODUCT_TYPE_BUNDLE = 'bundle';
    public const PRODUCT_TYPES = [
        self::PRODUCT_TYPE_SIMPLE,
        self::PRODUCT_TYPE_CONFIGURABLE,
        self::PRODUCT_TYPE_GROUPED,
        self::PRODUCT_TYPE_BUNDLE
    ];

    /**
     * @var ProductRepositoryInterface;
     */
    protected $_productRepository;

    /**
     * @var TaxRateFactory;
     */
    protected $taxRateFactory;

    /**
     * @var array|object
     */
    protected $productTaxRates;

    /**
     * @var ItemSource
     */
    protected $itemSource;

    /**
     * @var StockFilter
     */
    protected $stockFilter;

    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * @var LoggerInterface
     */
    protected $clerkLogger;

    /**
     * @var RequestInterface
     */
    protected $requestInterface;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var StockStateInterface
     */
    protected $stockStateInterface;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadataInterface;

    /**
     * @var string
     */
    protected $eventPrefix = 'product';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Data
     */
    protected $taxHelper;

    /**
     * @var array
     */
    protected $fieldMap = [
        'entity_id' => 'id',
    ];
    /**
     * @var ModuleManager
     */
    protected $moduleManager;
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var bool
     */
    protected $msiEnabled;
    /**
     * @var string
     */
    protected $scope;
    /**
     * @var int|string
     */
    protected $scopeId;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Image $imageHelper
     * @param ClerkLogger $clerkLogger
     * @param StockFilter $stockFilter
     * @param Data $taxHelper
     * @param StockStateInterface $stockStateInterface
     * @param ProductMetadataInterface $productMetadataInterface
     * @param RequestInterface $requestInterface
     * @param TaxRateFactory $taxRate
     * @param ProductRepositoryInterface $productRepository
     * @param ModuleManager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ScopeConfigInterface       $scopeConfig,
        ManagerInterface           $eventManager,
        CollectionFactory          $collectionFactory,
        StoreManagerInterface      $storeManager,
        Image                      $imageHelper,
        ClerkLogger                $clerkLogger,
        StockFilter                $stockFilter,
        Data                       $taxHelper,
        StockStateInterface        $stockStateInterface,
        ProductMetadataInterface   $productMetadataInterface,
        RequestInterface           $requestInterface,
        TaxRateFactory             $taxRate,
        ProductRepositoryInterface $productRepository,
        ModuleManager              $moduleManager,
        ObjectManagerInterface     $objectManager
    ) {
        $this->taxHelper = $taxHelper;
        $this->stockFilter = $stockFilter;
        $this->clerkLogger = $clerkLogger;
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->stockStateInterface = $stockStateInterface;
        $this->productMetadataInterface = $productMetadataInterface;
        $this->requestInterface = $requestInterface;
        $this->taxRateFactory = $taxRate;
        $this->productTaxRates = $this->taxRateFactory->create()->getData();
        $this->_productRepository = $productRepository;
        $this->msiEnabled = $this->moduleManager->isEnabled('Magento_Inventory')
            && $this->moduleManager->isEnabled('Magento_InventoryAdminUi');
        parent::__construct(
            $scopeConfig,
            $eventManager,
            $storeManager,
            $collectionFactory,
            $clerkLogger
        );
    }

    /**
     * Prepare collection
     *
     * @param int|string $page
     * @param int|string $limit
     * @param int|string $orderBy
     * @param int|string $order
     * @param int|string $scope
     * @param int|string $scopeid
     * @return mixed|void
     */
    protected function prepareCollection($page, $limit, $orderBy, $order, $scope, $scopeid)
    {
        try {

            $collection = $this->collectionFactory->create();

            $collection->addFieldToSelect('*');
            $collection->addStoreFilter($scopeid);
            $productMetadata = $this->productMetadataInterface;
            $version = $productMetadata->getVersion();

            if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, $scope, $scopeid)) {
                if (!$version >= '2.3.3') {
                    $this->stockFilter->addInStockFilterToCollection($collection);
                } else {
                    $collection->setFlag('has_stock_status_filter', true);
                }
            }

            $visibility = $this->scopeConfig->getValue(
                Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY,
                $scope,
                $scopeid
            );

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
                case 'any':
                    $collection->addAttributeToFilter('visibility', ['in' => [
                        Visibility::VISIBILITY_IN_CATALOG,
                        Visibility::VISIBILITY_IN_SEARCH,
                        Visibility::VISIBILITY_BOTH
                    ]]);
                    break;
            }

            $collection->setPageSize($limit)->setCurPage($page)->addOrder($orderBy, $order);

            $this->eventManager->dispatch('clerk_' . $this->eventPrefix . '_get_collection_after', [
                'adapter' => $this,
                'collection' => $collection
            ]);

            return $collection;

        } catch (Exception $e) {
            $this->clerkLogger->error('Prepare Collection Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add field handlers
     *
     * @return void
     */
    protected function addFieldHandlers()
    {

        try {

            $this->addFieldHandler('age', function ($item) {
                return floor((time() - strtotime($item->getCreatedAt())) / (60 * 60 * 24));
            });

            $this->addFieldHandler('created_at', function ($item) {
                return strtotime($item->getCreatedAt());
            });

            $this->addFieldHandler('product_type', function ($item) {
                return $item->getTypeId();
            });

            $this->addFieldHandler('manufacturer', function ($item) {
                return $this->getAttributeValue($item, 'manufacturer');
            });

            $this->addFieldHandler('description_html', function ($item) {
                return $this->getAttributeValue($item, 'description') ? htmlentities($this->getAttributeValue($item, 'description'), ENT_QUOTES) : '';
            });

            $this->addFieldHandler('description', function ($item) {
                return $this->getAttributeValue($item, 'description') ? str_replace(["\r", "\n"], ' ', strip_tags(html_entity_decode($this->getAttributeValue($item, 'description')))) : '';
            });

            $this->addFieldhandler('visibility', function ($item) {
                return $item->getattributetext('visibility');
            });

            $this->addFieldHandler('tax_rate', function ($item) {
                foreach ($this->productTaxRates as $tax) {
                    if (array_key_exists('tax_calculation_rate_id', $tax) && $item->getTaxClassId() == $tax['tax_calculation_rate_id']) {
                        return (float)$tax['rate'];
                    }
                }
                return 0;
            });

            $this->addFieldHandler('price', function ($item) {
                try {

                    $productType = $item->getTypeId();
                    $productTypeInstance = $item->getTypeInstance();

                    if ($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES)) {
                        return $this->formatPrice($this->getProductTaxPrice($item, $item->getFinalPrice(), true));
                    }
                    if ($productType == self::PRODUCT_TYPE_GROUPED) {
                        $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                        $groupedProductPriceTotal = 0;
                        if (!empty($associatedProducts)) {
                            foreach ($associatedProducts as $associatedProduct) {
                                $associatedProductQuantity = $associatedProduct->getQty();
                                $associatedProductPriceSource = null;
                                if ($associatedProduct->getFinalPrice()) {
                                    $associatedProductPriceSource = $associatedProduct->getFinalPrice();
                                } elseif ($associatedProduct->getPrice()) {
                                    $associatedProductPriceSource = $associatedProduct->getPrice();
                                }
                                if (isset($associatedProductPriceSource) && $associatedProductQuantity) {
                                    $groupedProductPriceTotal += $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProductPriceSource, true)) * $associatedProductQuantity;
                                }
                            }
                        }
                        return $groupedProductPriceTotal;
                    }

                    if ($productType == self::PRODUCT_TYPE_BUNDLE) {
                        return $this->formatPrice($item->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue());
                    }
                    if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                        $childPrices = [];
                        $childProducts = $productTypeInstance->getUsedProducts($item);
                        if (!empty($childProducts)) {
                            foreach ($childProducts as $childProduct) {
                                if (is_numeric($childProduct->getFinalPrice()) && $childProduct->getFinalPrice() > 0) {
                                    $childPrices[] = $childProduct->getFinalPrice();
                                }
                            }
                        }
                        if (!empty($childPrices)) {
                            return $this->formatPrice($this->getProductTaxPrice($item, min($childPrices), true));
                        } else {
                            return $this->formatPrice($this->getProductTaxPrice($item, $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(), true));
                        }
                    }
                } catch (Exception $e) {
                    $this->clerkLogger->error('Getting Product Price Error', ['error' => $e->getMessage()]);
                }
            });

            $this->addFieldHandler('price_excl_tax', function ($item) {
                try {

                    $productType = $item->getTypeId();
                    $productTypeInstance = $item->getTypeInstance();

                    if ($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES)) {
                        return $this->formatPrice($this->getProductTaxPrice($item, $item->getFinalPrice(), false));
                    }
                    if ($productType == self::PRODUCT_TYPE_GROUPED) {
                        $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                        $groupedProductPriceTotal = 0;
                        if (!empty($associatedProducts)) {
                            foreach ($associatedProducts as $associatedProduct) {
                                $associatedProductQuantity = $associatedProduct->getQty();
                                $associatedProductPriceSource = null;
                                if ($associatedProduct->getFinalPrice()) {
                                    $associatedProductPriceSource = $associatedProduct->getFinalPrice();
                                } elseif ($associatedProduct->getPrice()) {
                                    $associatedProductPriceSource = $associatedProduct->getPrice();
                                }
                                if (isset($associatedProductPriceSource) && $associatedProductQuantity) {
                                    $groupedProductPriceTotal += $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProductPriceSource, false)) * $associatedProductQuantity;
                                }
                            }
                        }
                        return $groupedProductPriceTotal;
                    }

                    if ($productType == self::PRODUCT_TYPE_BUNDLE) {
                        return $this->formatPrice($item->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue());
                    }
                    if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                        $childPrices = [];
                        $childProducts = $productTypeInstance->getUsedProducts($item);
                        if (!empty($childProducts)) {
                            foreach ($childProducts as $childProduct) {
                                if (is_numeric($childProduct->getFinalPrice()) && $childProduct->getFinalPrice() > 0) {
                                    $childPrices[] = $childProduct->getFinalPrice();
                                }
                            }
                        }
                        if (!empty($childPrices)) {
                            return $this->formatPrice($this->getProductTaxPrice($item, min($childPrices), false));
                        } else {
                            return $this->formatPrice($this->getProductTaxPrice($item, $item->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(), false));
                        }
                    }
                } catch (Exception $e) {
                    $this->clerkLogger->error('Getting Product Price Exc Tax Error', ['error' => $e->getMessage()]);
                }
            });

            //Add list_price fieldhandler
            $this->addFieldHandler('list_price', function ($item) {
                try {

                    $productType = $item->getTypeId();
                    $productTypeInstance = $item->getTypeInstance();

                    if ($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES)) {
                        return $this->formatPrice($this->getProductTaxPrice($item, $item->getPrice(), true));
                    }
                    if ($productType == self::PRODUCT_TYPE_GROUPED) {
                        $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                        $groupedProductPriceTotal = 0;
                        if (!empty($associatedProducts)) {
                            foreach ($associatedProducts as $associatedProduct) {
                                $associatedProductQuantity = $associatedProduct->getQty();
                                $associatedProductPriceSource = null;
                                if ($associatedProduct->getPrice()) {
                                    $associatedProductPriceSource = $associatedProduct->getPrice();
                                }
                                if (isset($associatedProductPriceSource) && $associatedProductQuantity) {
                                    $groupedProductPriceTotal += $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProductPriceSource, true)) * $associatedProductQuantity;
                                }
                            }
                        }
                        return $groupedProductPriceTotal;
                    }

                    if ($productType == self::PRODUCT_TYPE_BUNDLE) {
                        return $this->formatPrice($item->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue());
                    }
                    if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                        $childPrices = [];
                        $childProducts = $productTypeInstance->getUsedProducts($item);
                        if (!empty($childProducts)) {
                            foreach ($childProducts as $childProduct) {
                                if (is_numeric($childProduct->getPrice()) && $childProduct->getPrice() > 0) {
                                    $childPrices[] = $childProduct->getPrice();
                                }
                            }
                        }
                        if (!empty($childPrices)) {
                            return $this->formatPrice($this->getProductTaxPrice($item, min($childPrices), true));
                        } else {
                            return $this->formatPrice($this->getProductTaxPrice($item, $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), true));
                        }
                    }
                } catch (Exception $e) {
                    $this->clerkLogger->error('Getting Product List Price Error', ['error' => $e->getMessage()]);
                }
            });

            $this->addFieldHandler('list_price_excl_tax', function ($item) {
                try {

                    $productType = $item->getTypeId();
                    $productTypeInstance = $item->getTypeInstance();

                    if ($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES)) {
                        return $this->formatPrice($this->getProductTaxPrice($item, $item->getPrice(), false));
                    }
                    if ($productType == self::PRODUCT_TYPE_GROUPED) {
                        $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                        $groupedProductPriceTotal = 0;
                        if (!empty($associatedProducts)) {
                            foreach ($associatedProducts as $associatedProduct) {
                                $associatedProductQuantity = $associatedProduct->getQty();
                                $associatedProductPriceSource = null;
                                if ($associatedProduct->getPrice()) {
                                    $associatedProductPriceSource = $associatedProduct->getPrice();
                                }
                                if (isset($associatedProductPriceSource) && $associatedProductQuantity) {
                                    $groupedProductPriceTotal += $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProductPriceSource, false)) * $associatedProductQuantity;
                                }
                            }
                        }
                        return $groupedProductPriceTotal;
                    }

                    if ($productType == self::PRODUCT_TYPE_BUNDLE) {
                        return $this->formatPrice($item->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue());
                    }
                    if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                        $childPrices = [];
                        $childProducts = $productTypeInstance->getUsedProducts($item);
                        if (!empty($childProducts)) {
                            foreach ($childProducts as $childProduct) {
                                if (is_numeric($childProduct->getPrice()) && $childProduct->getPrice() > 0) {
                                    $childPrices[] = $childProduct->getPrice();
                                }
                            }
                        }
                        if (!empty($childPrices)) {
                            return $this->formatPrice($this->getProductTaxPrice($item, min($childPrices), false));
                        } else {
                            return $this->formatPrice($this->getProductTaxPrice($item, $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), false));
                        }
                    }
                } catch (Exception $e) {
                    $this->clerkLogger->error('Getting Product List Price Exc Tax Error', ['error' => $e->getMessage()]);
                }
            });

            $this->addFieldHandler('tier_price_values', function ($item) {
                $tierPriceValues = [];
                $tierPrices = $item->getTierPrice();
                if (!empty($tierPrices)) {
                    foreach ($tierPrices as $tierPrice) {
                        if (isset($tierPrice['price'])) {
                            $tierPriceValues[] = $this->formatPrice($tierPrice['price']);
                        }
                    }
                }
                return $tierPriceValues;
            });

            $this->addFieldHandler('tier_price_quantities', function ($item) {
                $tierPriceQuantities = [];
                $tierPrices = $item->getTierPrice();
                if (!empty($tierPrices)) {
                    foreach ($tierPrices as $tierPrice) {
                        if (isset($tierPrice['price_qty'])) {
                            $tierPriceQuantities[] = (int)$tierPrice['price_qty'];
                        }
                    }
                }
                return $tierPriceQuantities;
            });

            //Add image fieldhandler
            $this->addFieldHandler('image', function ($item) {
                return $this->fixImagePath($this->imageHelper->getUrl($item));
            });

            //Add url fieldhandler
            $this->addFieldHandler('url', function ($item) {
                return $item->setStoreId($item->scopeId)->getUrlInStore();
            });

            //Add categories fieldhandler
            $this->addFieldHandler('categories', function ($item) {
                return $item->getCategoryIds();
            });

            $this->addFieldHandler('child_stocks', function ($item) {
                $productType = $item->getTypeID();
                $productTypeInstance = $item->getTypeInstance();
                $stockValues = [];
                if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                    $usedProducts = $productTypeInstance->getUsedProducts($item);
                    foreach ($usedProducts as $usedProduct) {
                        $stockValues[] = $this->getProductStockStateQty($usedProduct);
                    }
                }
                if ($productType == self::PRODUCT_TYPE_GROUPED) {
                    $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                    foreach ($associatedProducts as $associatedProduct) {
                        $stockValues[] = $this->getProductStockStateQty($associatedProduct);
                    }
                }
                return $stockValues;
            });

            $this->addFieldHandler('child_prices', function ($item) {
                $productType = $item->getTypeID();
                $productTypeInstance = $item->getTypeInstance();
                $childPrices = [];
                if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                    $usedProducts = $productTypeInstance->getUsedProducts($item);
                    foreach ($usedProducts as $usedProduct) {
                        $childPrices[] = $this->formatPrice($this->getProductTaxPrice($usedProduct, $usedProduct->getFinalPrice(), true));
                    }
                }
                if ($productType == self::PRODUCT_TYPE_GROUPED) {
                    $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                    foreach ($associatedProducts as $associatedProduct) {
                        $childPrices[] = $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProduct->getFinalPrice(), true));
                    }
                }
                return $childPrices;
            });

            $this->addFieldHandler('child_list_prices', function ($item) {
                $productType = $item->getTypeID();
                $productTypeInstance = $item->getTypeInstance();
                $childPrices = [];
                if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                    $usedProducts = $productTypeInstance->getUsedProducts($item);
                    foreach ($usedProducts as $usedProduct) {
                        $childPrices[] = $this->formatPrice($this->getProductTaxPrice($usedProduct, $usedProduct->getPrice(), true));
                    }
                }
                if ($productType == self::PRODUCT_TYPE_GROUPED) {
                    $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                    foreach ($associatedProducts as $associatedProduct) {
                        $childPrices[] = $this->formatPrice($this->getProductTaxPrice($associatedProduct, $associatedProduct->getPrice(), true));
                    }
                }
                return $childPrices;
            });

            $this->addFieldHandler('child_images', function ($item) {
                $heavyAttributeQuery = $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS_HEAVY_QUERY, 'store', $this->getStoreIdFromContext());
                $productType = $item->getTypeID();
                $productTypeInstance = $item->getTypeInstance();
                $childImages = [];
                if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                    if ($heavyAttributeQuery) {
                        $childIdsRaw = $productTypeInstance->getChildrenIds($item->getId());
                        if (!empty($childIdsRaw)) {
                            if (isset($childIdsRaw[0]) && is_array($childIdsRaw[0])) {
                                $childIds = $childIdsRaw[0];
                            } else {
                                $childIds = $childIdsRaw;
                            }
                        }
                        foreach ($childIds as $childId) {
                            // Emulate product even if disabled
                            $childProduct = $this->_productRepository->getById($childId);
                            $childImages[] = $this->fixImagePath($this->imageHelper->getUrl($childProduct));
                        }
                    } else {
                        $usedProducts = $productTypeInstance->getUsedProducts($item);
                        foreach ($usedProducts as $usedProduct) {
                            $childImages[] = $this->fixImagePath($this->imageHelper->getUrl($usedProduct));
                        }
                    }
                }
                if ($productType == self::PRODUCT_TYPE_GROUPED) {
                    $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                    foreach ($associatedProducts as $associatedProduct) {
                        $childImages[] = $this->fixImagePath($this->imageHelper->getUrl($associatedProduct));
                    }
                }
                return $childImages;
            });

            $this->addFieldHandler('stock', function ($item) {
                $productType = $item->getTypeID();
                $productTypeInstance = $item->getTypeInstance();

                $productStock = 0;

                if ($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES)) {
                    $productStock = $this->getProductStockStateQty($item);
                    // If stock was 0, try to get it without looking at the scope.
                    //if($productStock == 0){
                    //  $productStock = $this->getSaleableStockBySku($item->getSku());
                    //}
                }
                if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                    $usedProducts = $productTypeInstance->getUsedProducts($item);
                    foreach ($usedProducts as $usedProduct) {
                        $productStock += $this->getProductStockStateQty($usedProduct);
                    }
                    //if($productStock == 0){
                    //  foreach ($usedProducts as $usedProduct) {
                    //    $productStock += $this->getSaleableStockBySku($usedProduct->getSku());
                    //  }
                    //}
                }
                if ($productType == self::PRODUCT_TYPE_GROUPED) {
                    $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                    foreach ($associatedProducts as $associatedProduct) {
                        $productStock += $this->getProductStockStateQty($associatedProduct);
                    }
                    //if($productStock == 0){
                    //  foreach($associatedProducts as $associatedProduct){
                    //    $productStock += $this->getSaleableStockBySku($associatedProduct->getSku());
                    //  }
                    //}
                }
                if ($productType == self::PRODUCT_TYPE_BUNDLE) {
                    $productsArray = [];
                    $selectionCollection = $item->getTypeInstance(true)->getSelectionsCollection(
                        $item->getTypeInstance(true)->getOptionsIds($item),
                        $item
                    );

                    foreach ($selectionCollection as $proselection) {
                        $selectionArray = [];
                        $selectionArray['min_qty'] = $proselection->getSelectionQty();
                        $selectionArray['stock'] = $this->stockStateInterface->getStockQty($proselection->getProductId(), $item->getStore()->getWebsiteId());
                        $productsArray[$proselection->getOptionId()][$proselection->getSelectionId()] = $selectionArray;
                    }

                    $bundle_stock = 0;
                    foreach ($productsArray as $bundle_item) {
                        $bundle_option_min_stock = 0;
                        foreach ($bundle_item as $bundle_option) {
                            if ((integer)$bundle_option['min_qty'] <= $bundle_option['stock']) {
                                $bundle_option_min_stock = ($bundle_option_min_stock == 0) ? $bundle_option['stock'] : $bundle_option_min_stock;
                                $bundle_option_min_stock = ($bundle_option_min_stock < $bundle_option['stock']) ? $bundle_option['stock'] : $bundle_option_min_stock;
                            }
                        }
                        $bundle_stock = ($bundle_stock == 0) ? $bundle_option_min_stock : $bundle_stock;
                        $bundle_stock = ($bundle_stock < $bundle_option_min_stock) ? $bundle_option_min_stock : $bundle_stock;
                    }
                    $productStock = $bundle_stock;
                }
                return $productStock;
            });

            $this->addFieldHandler('multi_source_stock', function ($item) {
                $productType = $item->getTypeID();
                $productTypeInstance = $item->getTypeInstance();
                $productStock = 0;

                if ($productType == self::PRODUCT_TYPE_SIMPLE || !in_array($productType, self::PRODUCT_TYPES)) {
                    $productStock = $this->getSourceStockBySku($item->getSku());
                }
                if ($productType == self::PRODUCT_TYPE_CONFIGURABLE) {
                    $usedProducts = $productTypeInstance->getUsedProducts($item);
                    foreach ($usedProducts as $usedProduct) {
                        $productStock += $this->getSourceStockBySku($usedProduct->getSku());
                    }
                }
                if ($productType == self::PRODUCT_TYPE_GROUPED) {
                    $associatedProducts = $productTypeInstance->getAssociatedProducts($item);
                    foreach ($associatedProducts as $associatedProduct) {
                        $productStock += $this->getSourceStockBySku($associatedProduct->getSku());
                    }
                }
                if ($productType == self::PRODUCT_TYPE_BUNDLE) {
                    $productsArray = [];
                    $selectionCollection = $item->getTypeInstance(true)->getSelectionsCollection(
                        $item->getTypeInstance(true)->getOptionsIds($item),
                        $item
                    );

                    foreach ($selectionCollection as $proselection) {
                        $selectionArray = [];
                        $selectionArray['min_qty'] = $proselection->getSelectionQty();
                        $selectionArray['stock'] = $this->stockStateInterface->getStockQty($proselection->getProductId(), $item->getStore()->getWebsiteId());
                        $productsArray[$proselection->getOptionId()][$proselection->getSelectionId()] = $selectionArray;
                    }

                    $bundle_stock = 0;
                    foreach ($productsArray as $bundle_item) {
                        $bundle_option_min_stock = 0;
                        foreach ($bundle_item as $bundle_option) {
                            if ((integer)$bundle_option['min_qty'] <= $bundle_option['stock']) {
                                $bundle_option_min_stock = ($bundle_option_min_stock == 0) ? $bundle_option['stock'] : $bundle_option_min_stock;
                                $bundle_option_min_stock = ($bundle_option_min_stock < $bundle_option['stock']) ? $bundle_option['stock'] : $bundle_option_min_stock;
                            }
                        }
                        $bundle_stock = ($bundle_stock == 0) ? $bundle_option_min_stock : $bundle_stock;
                        $bundle_stock = ($bundle_stock < $bundle_option_min_stock) ? $bundle_option_min_stock : $bundle_stock;
                    }

                    $productStock = $bundle_stock;
                }
                return $productStock;
            });

        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Field Handlers Error', ['error' => $e->getMessage()]);
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

            $attributeResource = $resourceItem->getResource();

            if (!$attributeResource) {
                return parent::getAttributeValue($resourceItem, $field);
            }

            $attribute = $attributeResource->getAttribute($field);

            if (!is_bool($attribute) && is_object($attribute)) {
                if ($attribute->usesSource()) {
                    $source = $attribute->getSource();
                    if ($source) {
                        return $source->getOptionText($resourceItem[$field]);
                    }
                }
            }

            return parent::getAttributeValue($resourceItem, $field);

        } catch (Exception $e) {

            $this->clerkLogger->error('Getting Attribute Value Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Format Price to 2 decimals
     * @param float|int $price
     * @return float|int $price
     */
    protected function formatPrice($price)
    {
        return (float)number_format((float)$price, 2, ".", "");
    }

    /**
     * Get Product price with contextual taxes
     */

    protected function getProductTaxPrice($product, $price, $withTax = true)
    {
        $store = $this->getStoreFromContext();
        return $this->taxHelper->getTaxPrice($product, $price, $withTax, null, null, null, $store, null, true);
    }

    /**
     * Format Image Path Valid
     * @param string $imagePath
     * @return string $imagePath
     */
    protected function fixImagePath($imagePath)
    {
        if (strpos($imagePath, 'catalog/product/') > -1) {
            return $imagePath;
        } else {
            return str_replace('catalog/product', 'catalog/product/', $imagePath);
        }
    }

    /**
     * Get Product stock from interface
     */
    protected function getProductStockStateQty($product)
    {
        $product_stock = $this->stockStateInterface->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
        return $product_stock ?? 0;
    }

    /**
     * Get source stock from SKU
     * @param string|int $sku
     * @return int
     */
    protected function getSourceStockBySku($sku)
    {
        $stockTotal = 0;
        if ($this->msiEnabled) {
            $sourceItems = $this->objectManager->create('Magento\Inventory\Model\SourceItem\Command\GetSourceItemsBySku')->execute($sku);
            foreach ($sourceItems as $sourceItem) {
                $stockTotal += $sourceItem->getQuantity();
            }
        }
        return $stockTotal;
    }

    /**
     * Get Global Stock
     * @param string|int $sku
     * @return int
     */

    protected function getSaleableStockBySku($sku)
    {
        $stockQuantity = 0;
        try {
            if ($this->msiEnabled) {
                $stockInfo = $this->objectManager->create('Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku')->execute($sku);
                if (!empty($stockInfo)) {
                    foreach ($stockInfo as $stockEntity) {
                        if (array_key_exists('qty', $stockEntity)) {
                            $stockQuantity += $stockEntity['qty'];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->clerkLogger->error('getSaleableStockBySku Error', ['error' => $e->getMessage()]);
        }
        return $stockQuantity;
    }

    /**
     * Get default fields to build for resourceItem
     *
     * @param string $scope
     * @param int|string $scopeid
     * @return array|void
     */
    protected function getDefaultFields($scope, $scopeid)
    {

        try {

            $this->scope = $scope;
            $this->scopeId = $scopeid;

            $fields = [
                'name',
                'description',
                'price',
                'price_excl_tax',
                'list_price',
                'list_price_excl_tax',
                'image',
                'url',
                'categories',
                'manufacturer',
                'sku',
                'age',
                'created_at',
                'stock',
                'product_type',
                'tier_price_values',
                'tier_price_quantities',
                'child_stocks',
                'child_images',
                'child_prices',
                'child_list_prices',
                'tax_rate'
            ];

            $additional_fields =
                $this->scopeConfig->getValue(
                    Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS,
                    $scope,
                    $scopeid
                );
            if ($additional_fields) {
                $fields = array_map('trim', explode(',', $additional_fields));
            }
            return array_values(array_unique($fields));

        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Default Fields Error', ['error' => $e->getMessage()]);
        }
    }
}
