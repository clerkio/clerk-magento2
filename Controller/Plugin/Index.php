<?php

namespace Clerk\Clerk\Controller\Plugin;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Api;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    protected $clerk_logger;

    protected $moduleList;

    /**
     * Version controller constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ModuleList $moduleList
     * @param ClerkLogger $clerk_logger
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     * @param Api $api
     */
    public function __construct(
        Context                  $context,
        StoreManagerInterface    $storeManager,
        ScopeConfigInterface     $scopeConfig,
        LoggerInterface          $logger,
        ModuleList               $moduleList,
        ClerkLogger              $clerk_logger,
        ProductMetadataInterface $product_metadata,
        RequestApi               $request_api,
        Api                      $api
    )
    {
        $this->moduleList = $moduleList;
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
     * Execute request
     */
    public function execute()
    {
        try {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            $response = $this->moduleList->getAll();

            if ($this->debug) {
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->getResponse()->setBody(json_encode($response));
            }
        } catch (Exception $e) {

            $this->clerk_logger->error('Plugin execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
