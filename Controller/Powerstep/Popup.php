<?php

namespace Clerk\Clerk\Controller\Powerstep;

use Clerk\Clerk\Block\PowerstepPopup;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

class Popup extends Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    public function __construct(Context $context, Session $checkoutSession)
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Dispatch request
     *
     * @return void
     */
    public function execute()
    {
        /** @var Page $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $layout = $response->addHandle('clerk_clerk_powerstep_popup')->getLayout();

        $response = $layout->getBlock('page.block')->toHtml();
        $this->getResponse()->setBody($response);

        return;
    }
}