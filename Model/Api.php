<?php

namespace Clerk\Clerk\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Store\Model\ScopeInterface;

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
     * @var string
     */
    protected $baseurl = 'https://api.clerk.io/v2/';

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * Api constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        ClerkLogger $Clerklogger,
        \Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->clerk_logger = $Clerklogger;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->requestInterface = $requestInterface;
    }

    /**
     * Add product
     */
    public function addProduct($params, $store_id = null)
    {
        try {
            $params = [
                'products' => [$params],
            ];

            $this->post('product/add', $params, $store_id);
            $this->clerk_logger->log('Added Product', ['response' => $params]);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Adding Products Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Perform a POST request
     *
     * @param string $endpoint
     * @param array $params
     * @throws \Exception
     */
    private function post($endpoint, $params = [], $store_id = null)
    {
        try {

            $params = array_merge($this->getDefaultParams($store_id), $params);

            $url = $this->baseurl . $endpoint;

            $response = $this->_curl_post($url, $params);

            return $response;

        } catch (\Exception $e) {

            $this->clerk_logger->error('POST Request Error', ['error' => $e->getMessage()]);

        }
    }

    private function getDefaultParams($store_id = null)
    {
        if (null === $store_id) {
            $_params = $this->requestInterface->getParams();
            $scope_id = '0';
            $scope = 'default';
            if (array_key_exists('website', $_params)) {
                $scope = 'website';
                $scope_id = $_params[$scope];
            }
            if (array_key_exists('store', $_params)) {
                $scope = 'store';
                $scope_id = $_params[$scope];
            }
        } else {
            $scope = 'store';
            $scope_id = $store_id;
        }

        return [
            'key' => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, $scope, $scope_id),
            'private_key' => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY, $scope, $scope_id),
        ];
    }

    /**
     * Remove product
     *
     * @param $productId
     * @throws \Exception
     */
    public function removeProduct($productId, $store_id = null)
    {
        try {
            // The API might expect a simple array of product IDs, not a JSON string
            $params = [
                'products' => [(string) $productId],
            ];

            // Log the exact parameters being sent for debugging
            $this->clerk_logger->log('Removing Product Request', [
                'product_id' => $productId,
                'store_id' => $store_id,
                'params' => $params
            ]);

            $response = $this->get('product/remove', $params, $store_id);
            
            // Log the response for debugging
            $this->clerk_logger->log('Removed Product Response', [
                'product_id' => $productId,
                'response' => $response
            ]);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Removing Products Error', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

        }
    }


    /**
     * @param $orderIncrementId
     * @param $product_id
     * @param $quantity
     * @return void
     */
    public function returnProduct($orderIncrementId, $product_id, $quantity, $store_id)
    {
        try {
            $params = [
                'product' => (string) $product_id,
                'order' => (string) $orderIncrementId,
                'quantity' => (int) $quantity
            ];
            $this->get('log/returned', $params, $store_id);
            $this->clerk_logger->log('Returned Product', ['response' => $params]);
        } catch (\Exception $e) {
            $this->clerk_logger->error('Returning Products Error', ['error' => $e->getMessage()]);
        }

    }
    private function _curl_get($url, $params = [])
    {
        try {

            if (!empty($params)) {
                $params = is_array($params) ? http_build_query($params) : $params;
                $url = $url . '?' . $params;
            }
            
            // Log the full URL for debugging
            $this->clerk_logger->log('CURL GET Request', [
                'url' => $url
            ]);
            
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            
            // Add additional options for better error handling
            curl_setopt($curl, CURLOPT_FAILONERROR, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($curl);
            
            // Check for curl errors
            $curl_error = curl_errno($curl);
            if ($curl_error) {
                $this->clerk_logger->error('CURL Error', [
                    'error_code' => $curl_error,
                    'error_message' => curl_error($curl),
                    'url' => $url
                ]);
            }
            
            // Get HTTP status code
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code >= 400) {
                $this->clerk_logger->error('HTTP Error', [
                    'http_code' => $http_code,
                    'url' => $url,
                    'response' => $response
                ]);
            }
            
            curl_close($curl);
            
            // Log the response for debugging
            $this->clerk_logger->log('CURL GET Response', [
                'http_code' => $http_code,
                'response' => $response
            ]);
            
            return $response;

        } catch (\Exception $e) {

            $this->clerk_logger->error('GET Request Error', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);

        }
    }

    private function _curl_post($url, $params = [])
    {
        try {

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_POST, true);
            if (!empty($params)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params, true));
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;

        } catch (\Exception $e) {

            $this->clerk_logger->error('POST Request Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Perform a GET request
     *
     * @param string $endpoint
     * @param array $params
     * @throws \Exception
     */
    private function get($endpoint, $params = [], $store_id = null)
    {
        try {

            $params = array_merge($this->getDefaultParams($store_id), $params);

            // Log the full request details for debugging
            $this->clerk_logger->log('GET Request Details', [
                'endpoint' => $endpoint,
                'url' => $this->baseurl . $endpoint,
                'params' => $params,
                'store_id' => $store_id
            ]);

            $url = $this->baseurl . $endpoint;

            $response = $this->_curl_get($url, $params);

            // Log the response for debugging
            $this->clerk_logger->log('GET Response', [
                'endpoint' => $endpoint,
                'response' => $response
            ]);

            return $response;

        } catch (\Exception $e) {

            $this->clerk_logger->error('GET Request Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'params' => $params
            ]);

        }
    }

    /**
     * Validate public & private key
     *
     * @param $publicKey
     * @param $privateKey
     * @return string
     * @throws \Exception
     */
    public function keysValid($publicKey, $privateKey)
    {
        try {

            $params = [
                'key' => $publicKey,
                'private_key' => $privateKey,
            ];

            return $this->get('client/account/info', $params);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Key Validation Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get available facet attributes
     *
     * @throws \Exception
     */
    public function getFacetAttributes()
    {
        try {

            $facetAttributesResponse = $this->get('product/facets');
            if ($facetAttributesResponse) {
                return json_decode($facetAttributesResponse);
            }

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Facet Attributes Error', ['error' => $e->getMessage()]);

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

                        return $content->api;

                    }
                }
            }

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Endpoint For Content Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get Clerk Content
     *
     * @param int $storeId
     * @return string
     * @throws \Exception
     */
    public function getContent($storeId = null)
    {
        try {
            $_params = $this->requestInterface->getParams();
            $scope_id = '0';
            $scope = 'default';
            if (array_key_exists('website', $_params)) {
                $scope = 'website';
                $scope_id = $_params[$scope];
            }
            if (array_key_exists('store', $_params)) {
                $scope = 'store';
                $scope_id = $_params[$scope];
            }
            $params = [
                'key' => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, $scope, $scope_id),
                'private_key' => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY, $scope, $scope_id),
            ];

            return $this->get('client/account/content/list', $params);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Getting Content Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Validate token with Clerk
     *
     * @param string $token_string
     * @param string $publicKey
     * @throws \Exception
     * @return array
     */
    public function verifyToken($token_string = null, $publicKey = null)
    {
        if (!$token_string || !$publicKey) {
            return false;
        }

        try {
            $query_params = array(
                'token' => $token_string,
                'key' => $publicKey,
            );

            $url = $this->baseurl . 'token/verify';
            $response = $this->_curl_get($url, $query_params);

            $decodedResponse = json_decode($response, true);

            return (array) $decodedResponse;

        } catch (\Exception $e) {

            $this->logger->error(' Communicator "postTokenVerification"', ['error' => $e->getMessage()]);

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
            'recommendations/new' => [
                'limit'
            ],
            'recommendations/specific' => [
                'limit'
            ],
            'recommendations/currently_watched' => [
                'limit'
            ],
            'recommendations/recently_bought' => [
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
            'recommendations/bundle' => [
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
            'recommendations/category/new' => [
                'limit',
                'category'
            ],
            'recommendations/category/complementary' => [
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

        return ['limit'];
    }
}
