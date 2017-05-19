<?php

namespace Clerk\Clerk\Controller\Version;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    protected $moduleList;

    /**
     * Version controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ModuleList $moduleList
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, LoggerInterface $logger, ModuleList $moduleList)
    {
        $this->moduleList = $moduleList;

        parent::__construct($context, $scopeConfig, $logger);
    }

    /**
     * Execute request
     */
    public function execute()
    {
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
    }
}