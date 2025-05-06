<?php
/**
 * Controller for getting a fresh form key via AJAX
 */

namespace Clerk\Clerk\Controller\Formkey;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;

class Get extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FormKey $formKey
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FormKey $formKey
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKey = $formKey;
    }

    /**
     * Execute action to get a fresh form key
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        return $result->setData(['formkey' => $this->formKey->getFormKey()]);
    }
}

