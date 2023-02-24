<?php

namespace Clerk\Clerk\Controller\Powerstep;

use Clerk\Clerk\Block\PowerstepPopup;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Clerk\Clerk\Controller\Logger\ClerkLogger;

class Popup extends Action
{
    /**
     * @var
     */
    protected $clerk_logger;
    /**
     * @var Session
     */
    protected $checkoutSession;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        ClerkLogger $clerk_logger
        )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->clerk_logger = $clerk_logger;
    }

    /**
     * Dispatch request
     *
     * @return void
     */
    public function execute()
    {
        try {

            /** @var Page $response */
            $response = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $layout = $response->addHandle('clerk_clerk_powerstep_popup')->getLayout();

            $response = $layout->getBlock('page.block')->toHtml();
            $this->getResponse()->setBody($response);
            return;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Powerstep execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
