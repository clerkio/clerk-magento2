<?php

namespace Clerk\Clerk\Controller\Version;

use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Api;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * @var ProductMetadataInterface
     */
    protected $_product_metadata;

    /**
     * Version controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ModuleList $moduleList
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     * @param Api $api
     */
    public function __construct(
        Context                  $context,
        ScopeConfigInterface     $scopeConfig,
        LoggerInterface          $logger,
        ModuleList               $moduleList,
        StoreManagerInterface    $storeManager,
        ClerkLogger              $clerk_logger,
        ProductMetadataInterface $product_metadata,
        RequestApi               $request_api,
        Api                      $api
    )
    {
        $this->moduleList = $moduleList;
        $this->clerk_logger = $clerk_logger;
        $this->_product_metadata = $product_metadata;
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
            $version = $this->_product_metadata->getVersion();

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            if ($this->storeManager->isSingleStoreMode()) {
                $scope = 'default';
                $scope_id = '0';
            } else {
                $scope = ScopeInterface::SCOPE_STORE;
                $scope_id = $this->storeManager->getStore()->getId();
            }

            $response = [
                'platform' => 'Magento2',
                'platform_version' => $version,
                'clerk_version' => $this->moduleList->getOne('Clerk_Clerk')['setup_version'],
                'php_version' => phpversion(),
                'scope' => $scope,
                'scope_id' => $scope_id
            ];

            if ($this->debug) {
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->getResponse()->setBody(json_encode($response));
            }
        } catch (Exception $e) {

            $this->clerk_logger->error('Version execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
