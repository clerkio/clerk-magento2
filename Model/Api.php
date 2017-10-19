<?php

namespace Clerk\Clerk\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Psr\Log\LoggerInterface;

class Api
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var string
     */
    protected $baseurl = 'http://api.clerk.io/v2/product/';

    /**
     * Api constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        ZendClientFactory $httpClientFactory
    )
    {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * Add product
     */
    public function addProduct($params)
    {
        $params = [
            'key'          => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY),
            'private_key'  => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY),
            'products'     => [$params],
        ];

        $this->post('add', $params);
    }

    /**
     * Remove product
     *
     * @param $productIds
     */
    public function removeProduct($productId)
    {
        $params = [
            'key'          => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY),
            'private_key'  => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY),
            'products'     => $productId,
        ];

        $this->get('remove', $params);
    }

    /**
     * Perform a GET request
     *
     * @param string $endpoint
     * @param array $params
     */
    private function get($endpoint, $params = [])
    {
        /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setUri($this->baseurl . $endpoint);
        $httpClient->setParameterGet($params);
        $httpClient->request('GET');
    }

    /**
     * Perform a POST request
     *
     * @param string $endpoint
     * @param array $params
     */
    private function post($endpoint, $params = [])
    {
        /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setUri($this->baseurl . $endpoint);
        $httpClient->setRawData(json_encode($params), 'application/json');

        $result = $httpClient->request('POST');
    }
}