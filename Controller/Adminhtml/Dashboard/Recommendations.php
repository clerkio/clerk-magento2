<?php

namespace Clerk\Clerk\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;

class Recommendations extends Action
{
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
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Clerk_Clerk::report_clerkroot_recommendations_insights');
        $resultPage->addBreadcrumb(__('Clerk.io - Recommendations Insights'), __('Clerk.io - Recommendations Insights'));
        $resultPage->getConfig()->getTitle()->prepend(__('Clerk.io - Recommendations Insights'));

        return $resultPage;
    }
}