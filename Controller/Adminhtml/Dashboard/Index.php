<?php

namespace Clerk\Clerk\Controller\Adminhtml\Dashboard;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
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
        $this->clerk_logger = $clerk_logger;
    }

    /**
     * Execute page function
     *
     * @return Page|void
     */
    public function execute()
    {
        try {

            /** @var Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Clerk_Clerk::report_clerkroot_dashboard');
            $resultPage->addBreadcrumb(__('Clerk.io Dashboard'), __('Clerk.io Dashboard'));
            $resultPage->getConfig()->getTitle()->prepend(__('Clerk.io Dashboard'));

            return $resultPage;

        } catch (Exception $e) {

            $this->clerk_logger->error('Dashboard execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
