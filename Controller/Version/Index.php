<?php

namespace Clerk\Clerk\Controller\Version;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Framework\App\ProductMetadataInterface;

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
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ModuleList $moduleList,
        StoreManagerInterface $storeManager,
        ClerkLogger $clerk_logger,
        ProductMetadataInterface $product_metadata,
        RequestApi $request_api
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
            $request_api
        );
    }

    /**
     * Execute request
     */
    public function execute()
    {
        try {
            $version = $this->_product_metadata->getVersion();

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            if ($this->_storeManager->isSingleStoreMode()) {
                $scope = 'default';
                $scope_id = '0';
            } else {
                $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                $scope_id = $this->_storeManager->getStore()->getId();
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
        } catch (\Exception $e) {

            $this->clerk_logger->error('Version execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
