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
     * @param $productId
     * @throws \Zend_Http_Client_Exception
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
     * @throws \Zend_Http_Client_Exception
     */
    public function keysValid($publicKey, $privateKey)
    {
        $params = [
            'key' => $publicKey,
            'private_key' => $privateKey,
        ];

        return $this->get('client/account/info', $params)->getBody();
    }

    /**
     * Get available facet attributes
     *
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    public function getFacetAttributes()
    {
        return $this->get('product/facets');
    }

    /**
     * Get Clerk Content
     *
     * @param null $storeId
     * @return string
     * @throws \Zend_Http_Client_Exception
     */
    public function getContent($storeId = null)
    {
        $params = [
            'key'         => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY),
            'private_key' => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY),
        ];

        return $this->get('client/account/content/list', $params)->getBody();
    }

    public function getEndpointForContent($contentId)
    {
        $contentResult = json_decode($this->getContent());

        if ($contentResult) {
            foreach ($contentResult->contents as $content) {
                if ($content->type !== 'html') {
                    continue;
                }

                if ($content->id === $contentId) {
                    return $content->api;
                }
            }
        }
    }

    public function getParametersForEndpoint($endpoint)
    {
        $endpointMap = [
            'search/search' => [
                'query',
                'limit'
            ],
            'search/predictive' => [
                'query',
                'limit'
            ],
            'search/categories' => [
                'query',
                'limit'
            ],
            'search/suggestions' => [
                'query',
                'limit'
            ],
            'search/popular' => [
                'query',
                'limit'
            ],
            'recommendations/popular' => [
                'limit'
            ],
            'recommendations/trending' => [
                'limit'
            ],
            'recommendations/currently_watched' => [
                'limit'
            ],
            'recommendations/popular' => [
                'limit'
            ],
            'recommendations/keywords' => [
                'limit',
                'keywords'
            ],
            'recommendations/complementary' => [
                'limit',
                'products'
            ],
            'recommendations/substituting' => [
                'limit',
                'products'
            ],
            'recommendations/category/popular' => [
                'limit',
                'category'
            ],
            'recommendations/category/trending' => [
                'limit',
                'category'
            ],
            'recommendations/visitor/history' => [
                'limit',
            ],
            'recommendations/visitor/complementary' => [
                'limit',
            ],
            'recommendations/visitor/substituting' => [
                'limit',
            ],
            'recommendations/customer/history' => [
                'limit',
                'email'
            ],
            'recommendations/customer/complementary' => [
                'limit',
                'email'
            ],
            'recommendations/customer/substituting' => [
                'limit',
                'email'
            ],
        ];

        if (array_key_exists($endpoint, $endpointMap)) {
            return $endpointMap[$endpoint];
        }

        return false;
    }

    /**
     * Perform a GET request
     *
     * @param string $endpoint
     * @param array $params
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
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
     * @throws \Zend_Http_Client_Exception
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