<?php

namespace Clerk\Clerk\Controller\Cart;

use Magento\Catalog\Controller\Product;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;

class Added extends Product
{
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
    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;

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
        $product = $this->_initProduct();

        if (!$product) {
            //Redirect to frontpage
            $this->_redirect('/');
            return;
        }

        return $this->resultPageFactory->create();
    }
}