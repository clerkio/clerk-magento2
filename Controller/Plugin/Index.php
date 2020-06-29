<?php

namespace Clerk\Clerk\Controller\Plugin;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Model\StoreManagerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    protected $clerk_logger;

    protected $moduleList;

    /**
     * Version controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ModuleList $moduleList
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager, ScopeConfigInterface $scopeConfig, LoggerInterface $logger, ModuleList $moduleList, ClerkLogger $ClerkLogger)
    {
        $this->moduleList = $moduleList;
        $this->clerk_logger = $ClerkLogger;
        parent::__construct($context, $storeManager, $scopeConfig, $logger, $moduleList, $ClerkLogger);
    }

    /**
     * Execute request
     */
    public function execute()
    {
        try {
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            $response = $this->moduleList->getAll();

            if ($this->debug) {
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->getResponse()->setBody(json_encode($response));
            }
        } catch (\Exception $e) {

            $this->clerk_logger->error('Plugin execute ERROR', ['error' => $e->getMessage()]);

        }

    }
}
