<?php

namespace Clerk\Clerk\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;

class Index extends Action
{
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(json_encode(['products' => [], 'categories' => [], 'sales' => [], 'pages' => []], JSON_PRETTY_PRINT));
    }
}