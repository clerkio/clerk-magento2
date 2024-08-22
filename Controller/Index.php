<?php

namespace Clerk\Clerk\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;

class Index extends Action
{
    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(json_encode(['products' => [], 'categories' => [], 'sales' => [], 'pages' => []], JSON_PRETTY_PRINT));
    }
}
