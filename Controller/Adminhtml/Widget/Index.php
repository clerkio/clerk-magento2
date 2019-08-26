<?php

namespace Clerk\Clerk\Controller\Adminhtml\Widget;

use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config\Source\Content;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Option\ArrayPool;
use Clerk\Clerk\Controller\Logger\ClerkLogger;

class Index extends Action
{
    /**
     * @var
     */
    protected $clerk_logger;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var ArrayPool
     */
    protected $sourceModelPool;

    /**
     * Index constructor.
     *
     * @param Action\Context $context
     * @param Api $api
     * @param FormFactory $formFactory
     * @param ArrayPool $sourceModelPool
     */
    public function __construct(Action\Context $context, Api $api, FormFactory $formFactory, ArrayPool $sourceModelPool, ClerkLogger $clerkLogger)
    {
        $this->api = $api;
        $this->formFactory = $formFactory;
        $this->sourceModelPool = $sourceModelPool;
        $this->clerk_logger = $clerkLogger;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {

            $type = $this->getRequest()->getParam('type', 'content');

            switch ($type) {
                case 'content':
                    $this->getContentResponse();
                    break;
                case 'parameters':
                    $this->getParametersResponse();
                    break;
                default:
                    $this->getInvalidResponse();
            }
        } catch (\Exception $e) {

            $this->clerk_logger->error('Widget execute ERROR', ['error' => $e->getMessage()]);

        }
    }

    public function getContentResponse()
    {
        try {
            /** @var Form $form */
            $form = $this->formFactory->create();

            /** @var \Magento\Framework\Data\Form\Element\Select $select */
            $select = $this->_objectManager->create('Magento\Framework\Data\Form\Element\Select');
            $select->setHtmlId('clerk_widget_content');
            $select->setId('clerk_widget_content');
            $select->setCssClass('clerk_content_select');
            $select->setName('parameters[content]');
            $select->setValues($this->sourceModelPool->get(Content::class)->toOptionArray());
            $select->setLabel(__('Content'));
            $select->setForm($form);

            $renderer = $this->_objectManager->create('Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element');

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true)
                ->representJson(
                    json_encode([
                        'success' => true,
                        'content' => $renderer->render($select)
                    ])
                );

        } catch (\Exception $e) {

            $this->clerk_logger->error('Widget getContentResponse ERROR', ['error' => $e->getMessage()]);

        }
    }

    public function getParametersResponse()
    {
        try {
            
            $content = $this->getRequest()->getParam('content');

            $endpoint = $this->api->getEndpointForContent($content);
            $parameters = $this->api->getParametersForEndpoint($endpoint);

            $html = '';

            if (!!array_intersect(['products', 'category'], $parameters)) {
                /** @var Form $form */
                $form = $this->formFactory->create();
                $form->setFieldsetRenderer($this->_objectManager->create(
                    'Magento\Backend\Block\Widget\Form\Renderer\Fieldset'
                ));
                $form->setUseContainer(false);

                $fieldset = $form->addFieldset('clerk_widget_options', [
                    'legend' => __('Clerk Content Options'),
                    'class' => 'fieldset-wide fieldset-widget-options clerk_widget_parameters',
                ]);

                if (in_array('products', $parameters)) {
                    $label = $fieldset->addField('product_id', 'label', [
                        'name' => $form->addSuffixToName('product_id', 'parameters'),
                        'class' => 'widget-option',
                        'label' => __('Product')
                    ]);

                    $chooser = $this->_objectManager->create('\Magento\Catalog\Block\Adminhtml\Product\Widget\Chooser');
                    $chooser->setHtmlId('clerk_widget_content');
                    $chooser->setConfig([
                        'button' => [
                            'open' => __('Select Product...')
                        ]
                    ]);
                    $chooser->setId('clerk_widget_content');
                    $chooser->setElement($label);
                    $chooser->setFieldsetId('clerk_widget_options');
                    $chooser->setCssClass('clerk_content_select');
                    $chooser->setName('parameters[content]');
                    $chooser->setLabel(__('Content'));
                    $chooser->setForm($form);

                    $chooser->prepareElementHtml($label);
                }

                if (in_array('category', $parameters)) {
                    $label = $fieldset->addField('category_id', 'label', [
                        'name' => $form->addSuffixToName('category_id', 'parameters'),
                        'class' => 'widget-option',
                        'label' => __('Category')
                    ]);

                    $chooser = $this->_objectManager->create('\Magento\Catalog\Block\Adminhtml\Category\Widget\Chooser');
                    $chooser->setHtmlId('clerk_widget_content');
                    $chooser->setConfig([
                        'button' => [
                            'open' => __('Select Category...')
                        ]
                    ]);
                    $chooser->setId('clerk_widget_content');
                    $chooser->setElement($label);
                    $chooser->setFieldsetId('clerk_widget_options');
                    $chooser->setCssClass('clerk_content_select');
                    $chooser->setName('parameters[content]');
                    $chooser->setLabel(__('Content'));
                    $chooser->setForm($form);

                    $chooser->prepareElementHtml($label);
                }

                $html .= $form->toHtml();
            }

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true)
                ->representJson(
                    json_encode([
                        'success' => true,
                        'content' => $html
                    ])
                );

        } catch (\Exception $e) {

            $this->clerk_logger->error('Widget getParametersResponse ERROR', ['error' => $e->getMessage()]);

        }
    }

    public function getInvalidResponse()
    {
        try {
            
            $this->getResponse()
                ->setHttpResponseCode(422)
                ->setHeader('Content-Type', 'application/json', true)
                ->representJson(
                    json_encode([
                        'success' => false,
                        'content' => 'invalid type'
                    ])
                );

        } catch (\Exception $e) {

            $this->clerk_logger->error('Widget getInvalidResponse ERROR', ['error' => $e->getMessage()]);

        }
    }


}