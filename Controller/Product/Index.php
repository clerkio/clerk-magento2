<?php

namespace Clerk\Clerk\Controller\Product;

use Clerk\Clerk\Model\Adapter\Product as ProductAdapter;
use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Adapter\Product;
use Clerk\Clerk\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;

class Index extends AbstractAction
{
    /**
     * @var
     */
    protected $clerk_logger;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var ProductAdapter
     */
    protected $productAdapter;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param Product $productAdapter
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ProductAdapter $productAdapter,
        ClerkLogger $ClerkLogger,
        \Magento\Catalog\Helper\Data $taxHelper
    )
    {
        $this->taxHelper = $taxHelper;
        $this->productAdapter = $productAdapter;
        $this->clerk_logger = $ClerkLogger;
        parent::__construct($context, $scopeConfig, $logger, $ClerkLogger);
    }

    /**
     *
     */
    public function execute()
    {
        try {

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            $response = $this->productAdapter->getResponse($this->fields, $this->page, $this->limit, $this->orderBy, $this->order);

            foreach ($response as $key => $product) {

                $price = '';
                $list_price = '';
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $product = $objectManager->create('Magento\Catalog\Model\Product')->load($product['id']);
                $productType = $product->getTypeID();

                if ($productType == "grouped") {
                    $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);

                    if (!empty($associatedProducts)) {


                        foreach ($associatedProducts as $associatedProduct) {

                            if (empty($price)) {

                                $price = $this->taxHelper->getTaxPrice($associatedProduct, $associatedProduct->getFinalPrice(), true);

                            } elseif ($price > $associatedProduct->getPrice()) {

                                $price = $this->taxHelper->getTaxPrice($associatedProduct, $associatedProduct->getFinalPrice(), true);

                            }

                        }

                    }

                    $response[$key]['price'] = (float) floatval($price)  ? (float) floatval($price) : 0;
                    $response[$key]['list_price'] = (float)floatval($price) ? floatval($price) : 0;

                }
                
            }

            $this->clerk_logger->log('Feched page ' . $this->page . ' with ' . count($response) . ' products', ['response' => $response]);

            $this->getResponse()->setBody(json_encode($response));

        } catch (\Exception $e) {

            $this->clerk_logger->error('Product execute ERROR', ['error' => $e->getMessage()]);

        }
    }

    /**
     * @param RequestInterface $request
     * @todo Remove this once everything is refactored to use adapters
     */
    protected function getArguments(RequestInterface $request)
    {
        try {

            $this->debug = (bool)$request->getParam('debug', false);
            $this->limit = (int)$request->getParam('limit', 0);
            $this->page = (int)$request->getParam('page', 0);
            $this->orderBy = $request->getParam('orderby', 'entity_id');

            if ($request->getParam('order') === 'desc') {
                $this->order = \Magento\Framework\Data\Collection::SORT_ORDER_DESC;
            } else {
                $this->order = \Magento\Framework\Data\Collection::SORT_ORDER_ASC;
            }

            /**
             * Explode fields on , and filter out "empty" entries
             */
            $fields = $request->getParam('fields');
            if ($fields) {
                $this->fields = array_filter(explode(',', $fields), 'strlen');
            }

        } catch (\Exception $e) {

            $this->clerk_logger->error('Product getArguments ERROR', ['error' => $e->getMessage()]);

        }
    }
}
