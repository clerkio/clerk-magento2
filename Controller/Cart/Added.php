<?php

namespace Clerk\Clerk\Controller\Cart;

use Magento\Catalog\Controller\Product;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
use Clerk\Clerk\Controller\Logger\ClerkLogger;

class Added extends Product
{
    /**
     * @var
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
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ClerkLogger $clerk_logger
        )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->clerk_logger = $clerk_logger;
        parent::__construct($context);
    }


    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {

            $product = $this->_initProduct();

            if (!$product) {
                //Redirect to frontpage
                $this->_redirect('/');
                return;
            }

            return $this->resultPageFactory->create();

        } catch (\Exception $e) {

            $this->clerk_logger->error('Cart execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
