<?php

namespace Clerk\Clerk\Block\Adminhtml\System\Config\Fieldset;

use Clerk\Clerk\Helper\Config as ConfigHelper;
use Clerk\Clerk\Model\Api;
use Clerk\Clerk\Model\Config;
use Exception;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\ResourceModel\Config as SystemConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;

class FacetedSearch extends Fieldset
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * FacetedSearch constructor.
     *
     * @param ConfigHelper $configHelper
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param RequestInterface $requestInterface
     * @param Api $api
     * @param SystemConfig $systemConfig
     * @param array $data
     */
    public function __construct(
        ConfigHelper     $configHelper,
        Context          $context,
        Session          $authSession,
        Js               $jsHelper,
        RequestInterface $requestInterface,
        Api              $api,
        SystemConfig     $systemConfig,
        array            $data = []
    )
    {
        $this->api = $api;
        $this->systemConfig = $systemConfig;
        $this->requestInterface = $requestInterface;
        $this->configHelper = $configHelper;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Render fieldset
     *
     * @param AbstractElement $element
     * @return string
     * @throws Exception
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        $header = $this->_getHeaderHtml($element);

        if (!$this->isConfigured()) {
            $elements = __('Public and private key must be set in order to enable faceted search');
        } else {
            if (!$this->keysValid()) {
                $elements = __('Public or private key invalid');
            } else {
                $elements = $this->_getChildrenElementsHtml($element);
            }
        }


        $footer = $this->_getFooterHtml($element);

        return $header . $elements . $footer;
    }

    /**
     * Determine if private & public key is set
     *
     * @return bool
     */
    private function isConfigured()
    {
        return $this->configHelper->getValueAdmin(Config::XML_PATH_PUBLIC_KEY) && $this->configHelper->getValueAdmin(Config::XML_PATH_PRIVATE_KEY);
    }

    /**
     * Determine if public & private keys are valid
     *
     * @return bool
     * @throws Exception
     */
    private function keysValid()
    {
        $publicKey = $this->configHelper->getValueAdmin(Config::XML_PATH_PUBLIC_KEY);
        $privateKey = $this->configHelper->getValueAdmin(Config::XML_PATH_PRIVATE_KEY);

        $keysValid = json_decode($this->api->keysValid($publicKey, $privateKey));

        if ($keysValid->status === 'error') {
            return false;
        }

        return true;
    }
}
