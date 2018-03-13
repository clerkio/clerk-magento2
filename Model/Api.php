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
    protected $baseurl = 'http://api.clerk.io/v2/';

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
            'products' => [$params],
        ];

        $this->post('product/add', $params);
    }

    /**
     * Remove product
     *
     * @param $productIds
     */
    public function removeProduct($productId)
    {
        $params = [
            'products'     => [$productId],
        ];

        // work around a problem that API fails with query like ?products[0]=123&products[1]=456
        // make it like this: ?products[]=123&products[]=456
        $query = http_build_query($params);
        $query = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $query);

        $this->get('product/remove?' . $query);
    }

    /**
     * Validate public & private key
     *
     * @param $publicKey
     * @param $privateKey
     * @return string
     */
    public function keysValid($publicKey, $privateKey)
    {
        $params = [
            'key' => $publicKey,
            'private_key' => $privateKey,
        ];

        return $this->get('client/account/info', $params)->getBody();
    }

    public function getFacetAttributes()
    {
        return $this->get('product/facets');
    }

    /**
     * Perform a GET request
     *
     * @param string $endpoint
     * @param array $params
     */
    private function get($endpoint, $params = [])
    {
        $params = array_merge($this->getDefaultParams(), $params);

        /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setUri($this->baseurl . $endpoint);
        $httpClient->setParameterGet($params);
        $response = $httpClient->request('GET');

        return $response;
    }

    /**
     * Perform a POST request
     *
     * @param string $endpoint
     * @param array $params
     */
    private function post($endpoint, $params = [])
    {
        $params = array_merge($this->getDefaultParams(), $params);

        /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setUri($this->baseurl . $endpoint);
        $httpClient->setRawData(json_encode($params), 'application/json');

        $result = $httpClient->request('POST');
    }

    private function getDefaultParams()
    {
        return [
            'key'         => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY),
            'private_key' => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY),
        ];
    }
}