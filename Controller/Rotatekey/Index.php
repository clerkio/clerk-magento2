<?php

namespace Clerk\Clerk\Controller\Rotatekey;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Cache\TypeListInterface as CacheType;
use Magento\Framework\Webapi\Rest\Request as RequestApi;
use Magento\Framework\Encryption\EncryptorInterface;

class Index extends AbstractAction
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

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
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    protected $ScopeConfigInterface;

    /**
     * @var ProductMetadataInterface
     */
    protected $_product_metadata;

    /**
     * @var CacheType
     */
    protected $_cacheType;

    /**
     * Version controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ModuleList $moduleList
     * @param ProductMetadataInterface $product_metadata
     * @param CacheType $cacheType
     * @param RequestApi $request_api
     * @param Api $api
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $ScopeConfigInterface,
        LoggerInterface $logger,
        ModuleList $moduleList,
        StoreManagerInterface $storeManager,
        ClerkLogger $clerk_logger,
        WriterInterface $configWriter,
        ProductMetadataInterface $product_metadata,
        CacheType $cacheType,
        RequestApi $request_api,
        Api $api,
        EncryptorInterface $encryptor
    ) {
        $this->clerk_logger = $clerk_logger;
        $this->config_writer = $configWriter;
        $this->_cacheType = $cacheType;
        $this->encryptor = $encryptor;
        parent::__construct(
            $context,
            $storeManager,
            $ScopeConfigInterface,
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

            $post = $this->getRequest()->getcontent();
            $scope = $this->getRequest()->getParam('scope');
            if ($scope !== 'default') {
                $scope = $scope . 's';
            }
            $scopeId = intval($this->getRequest()->getParam('scope_id'));

            $response = [
                'status' => 'error',
                'message' => 'Failed to update Private API key',
                'scope' => $scope,
                'scopeId' => $scopeId
            ];

            if ($post) {

                $body_array = json_decode($post, true) ? json_decode($post, true) : array();

                if (array_key_exists('clerk_private_key', $body_array)) {
                    $encryptedValue = $this->encryptor->encrypt($body_array['clerk_private_key']);

                    $this->config_writer->save(Config::XML_PATH_PRIVATE_KEY, $encryptedValue, $scope, $scopeId);
                    $this->_cacheType->cleanType('config');

                    $response = [
                        'status' => 'ok',
                        'message' => 'Updated Private API key',
                        'scope' => $scope,
                        'scopeId' => $scopeId
                    ];

                }

            }


            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            if ($this->debug) {
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->getResponse()->setBody(json_encode($response));
            }


        } catch (\Exception $e) {

            $this->clerk_logger->error('Rotatekey execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
