<?php

namespace Clerk\Clerk\Controller\Product;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Adapter\Product;
use Clerk\Clerk\Model\Adapter\Product as ProductAdapter;
use Clerk\Clerk\Model\Api;
use Exception;
use Magento\Catalog\Helper\Data;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * @var ProductAdapter
     */
    protected $productAdapter;

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * @var Data
     */
    protected $taxHelper;

    /**
     * @var ProductMetadataInterface
     */
    protected $_product_metadata;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param Product $productAdapter
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     * @param Api $api
     */
    public function __construct(
        Context                  $context,
        ScopeConfigInterface     $scopeConfig,
        StoreManagerInterface    $storeManager,
        ProductAdapter           $productAdapter,
        ClerkLogger              $clerk_logger,
        LoggerInterface          $logger,
        Data                     $taxHelper,
        ModuleList               $moduleList,
        ProductMetadataInterface $product_metadata,
        RequestApi               $request_api,
        Api                      $api
    )
    {
        $this->taxHelper = $taxHelper;
        $this->moduleList = $moduleList;
        $this->productAdapter = $productAdapter;
        $this->clerk_logger = $clerk_logger;
        parent::__construct(
            $context,
            $storeManager,
            $scopeConfig,
            $logger,
            $moduleList,
            $clerk_logger,
            $product_metadata,
            $request_api,
            $api
        );
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws FileSystemException
     */
    public function execute()
    {
        try {

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            if (isset($this->fields)) {

                if (!in_array('qty', $this->fields)) {

                    $this->fields[] = 'qty';

                }

            } else {

                $this->fields[] = 'qty';

            }

            $response = $this->productAdapter->getResponse($this->fields, $this->page, $this->limit, $this->orderBy, $this->order, $this->scope, $this->scopeid);

            if (is_array($response)) {
                $response = array_values(array_filter($response));
            }

            $this->clerk_logger->log('Feched page ' . $this->page . ' with ' . count($response) . ' products', ['response' => $response]);

            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

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
            $this->scopeid = $request->getParam('scope_id');
            $this->scope = $request->getParam('scope');

            if ($request->getParam('order') === 'desc') {
                $this->order = Collection::SORT_ORDER_DESC;
            } else {
                $this->order = Collection::SORT_ORDER_ASC;
            }

            /**
             * Explode fields on , and filter out "empty" entries
             */
            $fields = $request->getParam('fields');
            if ($fields) {
                $this->fields = array_filter(explode(',', $fields), 'strlen');
            }

        } catch (Exception $e) {

            $this->clerk_logger->error('Product getArguments ERROR', ['error' => $e->getMessage()]);

        }
    }
}
