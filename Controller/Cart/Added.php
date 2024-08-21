<?php

namespace Clerk\Clerk\Controller\Cart;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Exception;
use Magento\Catalog\Controller\Product;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Added extends Product
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
     * Added constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ClerkLogger $clerk_logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ClerkLogger $clerk_logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->clerk_logger = $clerk_logger;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return Page|void
     */
    public function execute()
    {
        try {

            $product = $this->_initProduct();
            if (!$product) {
                //Redirect to frontpage
                $this->_redirect('/');
            }
            return $this->resultPageFactory->create();

        } catch (Exception $e) {
            $this->clerk_logger->error('Cart execute ERROR', ['error' => $e->getMessage()]);
        }
    }
}
