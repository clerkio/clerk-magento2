<?php

namespace Clerk\Clerk\Controller\Adminhtml\Dashboard;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Email extends Action
{
    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ClerkLogger $clerk_logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ClerkLogger $clerk_logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->clerk_logger =$clerk_logger;
    }

    /**
     * Page execute function
     *
     * @return Page|void
     */
    public function execute()
    {
        try {

            /** @var Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Clerk_Clerk::report_clerkroot_email_insights');
            $resultPage->addBreadcrumb(__('Clerk.io - Email Insights'), __('Clerk.io - Email Insights'));
            $resultPage->getConfig()->getTitle()->prepend(__('Clerk.io - Email Insights'));

            return $resultPage;

        } catch (Exception $e) {

            $this->clerk_logger->error('Email execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
