<?php

namespace Clerk\Clerk\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;

class Api
{
    /**
     * @var LoggerInterface
     */
    protected $clerk_logger;

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
        ZendClientFactory $httpClientFactory,
        ClerkLogger $Clerklogger
    )
    {
        $this->clerk_logger = $Clerklogger;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * Add product
     */
    public function addProduct($params)
    {
        try {
            
            $params = [
                'products' => [$params],
            ];

            $this->post('product/add', $params);
            $this->clerk_logger->log('Adding Products Done', ['response' => $params]);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Adding Products Error', ['error' => $e]);

        }
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
        try {
            
            $params = array_merge($this->getDefaultParams(), $params);

            /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
            $httpClient = $this->httpClientFactory->create();
            $httpClient->setUri($this->baseurl . $endpoint);
            $httpClient->setRawData(json_encode($params), 'application/json');

            $this->clerk_logger->log('POST Request Done', ['response' => $response]);
            $result = $httpClient->request('POST');

        } catch (\Exception $e) {

            $this->clerk_logger->error('POST Request Error', ['error' => $e]);

        }
    }

    private function getDefaultParams()
    {
        return [
            'key' => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY),
            'private_key' => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY),
        ];
    }

    /**
     * Remove product
     *
     * @param $productId
     * @throws \Zend_Http_Client_Exception
     */
    public function removeProduct($productId)
    {
        try {
            
            $params = [
                'products' => $productId,
            ];

            $this->get('product/remove', $params);
            $this->clerk_logger->log('Removing Products Done', ['response' => $params]);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Removing Products Error', ['error' => $e]);

        }
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
        try {
            
            $params = array_merge($this->getDefaultParams(), $params);

            /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
            $httpClient = $this->httpClientFactory->create();
            $httpClient->setUri($this->baseurl . $endpoint);
            $httpClient->setParameterGet($params);
            $response = $httpClient->request('GET');

            $this->clerk_logger->log('GET Request Done', ['response' => $response]);

            return $response;

        } catch (\Exception $e) {

            $this->clerk_logger->error('GET Request Error', ['error' => $e]);

        }
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
        try {
            
            $params = [
                'key' => $publicKey,
                'private_key' => $privateKey,
            ];

            $this->clerk_logger->log('Key Validation Done', ['response' => $this->get('client/account/info', $params)->getBody()]);

            return $this->get('client/account/info', $params)->getBody();

        } catch (\Exception $e) {

            $this->clerk_logger->error('Key Validation Error', ['error' => $e]);

        }
    }

    /**
     * Get available facet attributes
     *
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    public function getFacetAttributes()
    {
        try {
            
            return $this->get('product/facets');

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Facet Attributes Error', ['error' => $e]);

        }
    }

    public function getEndpointForContent($contentId)
    {
        try {
            
            $contentResult = json_decode($this->getContent());

            if ($contentResult) {

                foreach ($contentResult->contents as $content) {

                    if ($content->type !== 'html') {

                        continue;

                    }

                    if ($content->id === $contentId) {

                        $this->clerk_logger->log('Getting Endpoint For Content Done', ['response' => $content->api]);
                        return $content->api;

                    }
                }
            }

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Endpoint For Content Error', ['error' => $e]);

        }
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
        try {
            
            $params = [
                'key' => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY),
                'private_key' => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY),
            ];

            $this->clerk_logger->log('Getting Content Done', ['response' => $this->get('client/account/content/list', $params)->getBody()]);

            return $this->get('client/account/content/list', $params)->getBody();

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Content Error', ['error' => $e]);

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
}