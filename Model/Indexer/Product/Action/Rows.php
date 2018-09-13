<?php

namespace Clerk\Clerk\Model\Indexer\Product\Action;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Clerk\Clerk\Model\Handler\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class Rows
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Type
     */
    protected $productType;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * @var Image
     */
    protected $imageHandler;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AbstractType[]
     */
    protected $compositeTypes;

    /**
     * @var \Clerk\Clerk\Helper\Product
     */
    protected $helper;

    /**
     * @var array
     */
    protected $fieldHandlers = [];

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param ProductRepository $productRepository
     * @param Api $api
     * @param Emulation $emulation
     * @param Image $imageHandler
     * @param StoreManagerInterface $storeManager
     * @param Type $productType
     * @param \Clerk\Clerk\Helper\Product $helper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        ProductRepository $productRepository,
        Api $api,
        Emulation $emulation,
        Image $imageHandler,
        StoreManagerInterface $storeManager,
        Type $productType,
        \Clerk\Clerk\Helper\Product $helper
    ) {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->productRepository = $productRepository;
        $this->productType = $productType;
        $this->api = $api;
        $this->emulation = $emulation;
        $this->imageHandler = $imageHandler;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * Execute action for given ids
     *
     * @param array|int $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function execute($ids = null)
    {
        if (!$this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED)) {
            return;
        }

        if (!isset($ids) || empty($ids)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t rebuild the index for an undefined product.')
            );
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        foreach ($ids as $id) {
            $this->reindexRow($id);
            $this->updateParentProducts($id);
        }
    }

    /**
     * Refresh entity index
     *
     * @param int $productId
     * @return void
     */
    protected function reindexRow($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $this->api->removeProduct($productId);
            return;
        }

        //Cancel if product visibility is not as defined
        if ($product->getVisibility() != $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY)) {
            $this->api->removeProduct($product->getId());
            return;
        }

        //Cancel if product is not saleable
        if ($this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY)) {
            if (!$this->helper->isSalable($product)) {
                $this->api->removeProduct($product->getId());
                return;
            }
        }

        $store = $this->storeManager->getDefaultStoreView();
        $this->emulation->startEnvironmentEmulation($store->getId());

        $imageUrl = $this->imageHandler->handle($product);

        $productItem = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getFinalPrice(),
            'list_price' => $product->getPrice(),
            'image' => $imageUrl,
            'url' => $product->getUrlModel()->getUrl($product),
            'categories' => $product->getCategoryIds(),
            'sku' => $product->getSku(),
            'on_sale' => ($product->getFinalPrice() < $product->getPrice()),
        ];

        /**
         * @todo Refactor to use fieldhandlers or similar
         */
        $configFields = $this->scopeConfig->getValue(
            Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS
        );

        $fields = explode(',', $configFields);

        $this->eventManager->dispatch('clerk_product_get_fields_before', [
            'subject' => $this,
            'collection' => $fields
        ]);

        foreach ($fields as $field) {
            if (!isset($productItem[$field]) && isset($product[$field])) {
                $productItem[$field] = $this->getAttributeValue($product, $field);
            }

            if (isset($this->fieldHandlers[$field])) {
                $item[$field] = $this->fieldHandlers[$field]($product);
            }
        }

        $productObject = new \Magento\Framework\DataObject();
        $productObject->setData($productItem);

        $this->eventManager->dispatch('clerk_product_sync_before', ['product' => $productObject]);

        $this->api->addProduct($productObject->toArray());

        $this->emulation->stopEnvironmentEmulation();
    }

    /**
     * Get attribute value for product
     *
     * @param Product $product
     * @param string $field
     * @return string
     * @see \Magento\Catalog\Block\Product\View\Attributes::getAdditionalData
     */
    protected function getAttributeValue(Product $product, $field)
    {
        $attribute = $product->getResource()->getAttribute($field);
        if ($attribute && $attribute->usesSource()) {
            return $attribute->getSource()->getOptionText($product[$field]);
        } else {
            return $product->getData($field);
        }
    }

    /**
     * Reindex parent configurable/bundle/grouped products
     *
     * @param int $productId
     * @return void
     * @see \Magento\Catalog\Model\Indexer\Product\Flat\AbstractAction::_getProductTypeInstances
     */
    public function updateParentProducts($productId)
    {
        $parentIds = [];

        foreach ($this->getCompositeTypes() as $typeInstance) {
            $parentIds = array_merge($parentIds, $typeInstance->getParentIdsByChild($productId));
        }

        foreach ($parentIds as $parentId) {
            $this->reindexRow($parentId);
        }
    }

    /**
     * @return AbstractType[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCompositeTypes()
    {
        if (null === $this->compositeTypes) {
            $productEmulator = new \Magento\Framework\DataObject();
            foreach ($this->productType->getCompositeTypes() as $typeId) {
                $productEmulator->setTypeId($typeId);
                $this->compositeTypes[$typeId] = $this->productType->factory($productEmulator);
            }
        }

        return $this->compositeTypes;
    }

    /**
     * @param string $field
     * @param callable $handler
     * @return $this
     */
    public function addFieldHandler($field, callable $handler)
    {
        $this->fieldHandlers[$field] = $handler;
        return $this;
    }
}
