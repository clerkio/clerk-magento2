<?php

namespace Clerk\Clerk\Controller\Product;

use Clerk\Clerk\Model\Adapter\Product as ProductAdapter;
use Clerk\Clerk\Controller\AbstractAction;
use Clerk\Clerk\Model\Adapter\Product;
use Clerk\Clerk\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class Index extends AbstractAction
{
    /**
     * @var ProductAdapter
     */
    protected $productAdapter;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param Product $productAdapter
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ProductAdapter $productAdapter
    )
    {
        $this->productAdapter = $productAdapter;
        parent::__construct($context, $scopeConfig, $logger);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/json', true);

        $response = $this->productAdapter->getResponse($this->fields, $this->page, $this->limit, $this->orderBy, $this->order);

        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * @param RequestInterface $request
     * @todo Remove this once everything is refactored to use adapters
     */
    protected function getArguments(RequestInterface $request)
    {
        $this->debug = (bool) $request->getParam('debug', false);
        $this->limit = (int) $request->getParam('limit', 0);
        $this->page = (int) $request->getParam('page', 0);
        $this->orderBy = $request->getParam('orderby', 'entity_id');

        if ($request->getParam('order') === 'desc') {
            $this->order = \Magento\Framework\Data\Collection::SORT_ORDER_DESC;
        } else {
            $this->order = \Magento\Framework\Data\Collection::SORT_ORDER_ASC;
        }

        /**
         * Explode fields on , and filter out "empty" entries
         */
        $fields = $request->getParam('fields');
        if ($fields) {
            $this->fields = array_filter(explode(',', $fields), 'strlen');
        }
    }
}
