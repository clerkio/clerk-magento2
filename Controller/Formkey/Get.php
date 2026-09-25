<?php
/**
 * Controller for getting a fresh form key via AJAX
 */

namespace Clerk\Clerk\Controller\Formkey;

use Clerk\Clerk\Model\Config;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EncoderInterface
     */
    protected $urlEncoder;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FormKey $formKey
     * @param CustomerSession|null $customerSession
     * @param ScopeConfigInterface|null $scopeConfig
     * @param StoreManagerInterface|null $storeManager
     * @param EncoderInterface|null $urlEncoder
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FormKey $formKey,
        ?CustomerSession $customerSession = null,
        ?ScopeConfigInterface $scopeConfig = null,
        ?StoreManagerInterface $storeManager = null,
        ?EncoderInterface $urlEncoder = null
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKey = $formKey;
        $objectManager = ObjectManager::getInstance();
        $this->customerSession = $customerSession ?: $objectManager->get(CustomerSession::class);
        $this->scopeConfig = $scopeConfig ?: $objectManager->get(ScopeConfigInterface::class);
        $this->storeManager = $storeManager ?: $objectManager->get(StoreManagerInterface::class);
        $this->urlEncoder = $urlEncoder ?: $objectManager->get(EncoderInterface::class);
    }

    /**
     * Returns the current visitor's form key and, when email collection is on, their email.
     * The response is per visitor, so it must never be stored by the page cache.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store', true);
        $result->setHeader('Pragma', 'no-cache', true);
        $data = ['formkey' => $this->formKey->getFormKey()];
        $currentUrl = $this->getRequest()->getParam('current_url');
        if ($this->isShopUrl($currentUrl)) {
            $data['uenc'] = $this->urlEncoder->encode($currentUrl);
        }
        $email = $this->getLoggedInEmail();
        if ($email !== '') {
            $data['email'] = $email;
        }

        return $result->setData($data);
    }

    /**
     * Designs read uenc as a return URL. Only a URL on this shop is encoded.
     *
     * @param mixed $url
     * @return bool
     */
    private function isShopUrl($url)
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $host = strtolower($parts['host']);
        $allowed = [];
        $requestHost = $this->getRequest()->getHttpHost();
        if (is_string($requestHost) && $requestHost !== '') {
            $allowed[] = strtolower((string)preg_replace('/:\d+$/', '', $requestHost));
        }

        try {
            $store = $this->storeManager->getStore();
            foreach ([false, true] as $secure) {
                $baseHost = parse_url($store->getBaseUrl(UrlInterface::URL_TYPE_LINK, $secure), PHP_URL_HOST);
                if (is_string($baseHost) && $baseHost !== '') {
                    $allowed[] = strtolower($baseHost);
                }
            }
        } catch (\Exception $e) {
            return in_array($host, $allowed, true);
        }

        return in_array($host, $allowed, true);
    }

    /**
     * Email belongs to the current visitor, so it is loaded per session
     * instead of being printed into a cached page.
     *
     * @return string
     */
    private function getLoggedInEmail()
    {
        try {
            if ($this->storeManager->isSingleStoreMode()) {
                $scope = 'default';
                $scopeId = '0';
            } else {
                $scope = ScopeInterface::SCOPE_STORE;
                $scopeId = $this->storeManager->getStore()->getId();
            }

            if (!$this->scopeConfig->isSetFlag(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS, $scope, $scopeId)) {
                return '';
            }

            if (!$this->customerSession->isLoggedIn()) {
                return '';
            }

            $email = $this->customerSession->getCustomer()->getEmail();
            return is_string($email) ? $email : '';
        } catch (\Exception $e) {
            return '';
        }
    }
}

