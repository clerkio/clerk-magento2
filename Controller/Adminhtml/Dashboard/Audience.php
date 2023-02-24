<?php

namespace Clerk\Clerk\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;

class Audience extends Action
{
    /**
     * @var
     */
    protected $clerk_logger;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        ClerkLogger $clerk_logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->clerk_logger = $clerk_logger;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        try {

            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Clerk_Clerk::report_clerkroot_audience_insights');
            $resultPage->addBreadcrumb(__('Clerk.io - Audience Insights'), __('Clerk.io - Audience Insights'));
            $resultPage->getConfig()->getTitle()->prepend(__('Clerk.io - Audience Insights'));

            return $resultPage;

        } catch (\Exception $e) {

            $this->clerk_logger->error('Audience execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
