<?php

namespace Clerk\Clerk\Controller\Version;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;
use  Clerk\Clerk\Controller\Logger\ClerkLogger;

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
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, LoggerInterface $logger, ModuleList $moduleList, ClerkLogger $ClerkLogger)
    {
        $this->moduleList = $moduleList;
        $this->clerk_logger = $ClerkLogger;
        parent::__construct($context, $scopeConfig, $logger, $ClerkLogger);
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

            $response = [
                'platform' => 'Magento2',
                'version' => $this->moduleList->getOne('Clerk_Clerk')['setup_version'],
            ];

            if ($this->debug) {
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->getResponse()->setBody(json_encode($response));
            }
        } catch (\Exception $e) {

            $this->clerk_logger->error('Version execute ERROR', ['error' => $e]);

        }

    }
}