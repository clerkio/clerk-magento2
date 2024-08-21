<?php

namespace Clerk\Clerk\Model;

use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Clerk\Clerk\Helper\Context as ContextHelper;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class Api
{
    /**
     * @var Curl
     */
    public $curlClient;
    /**
     * @var LoggerInterface
     */
    protected $clerkLogger;
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
     * @var ContextHelper
     */
    protected $contextHelper;

    /**
     * Api constructor
     *
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param ClerkLogger $clerkLogger
     * @param RequestInterface $requestInterface
     * @param ContextHelper $contextHelper
     * @param Curl $curl
     */
    public function __construct(
        LoggerInterface      $logger,
        ScopeConfigInterface $scopeConfig,
        ClerkLogger          $clerkLogger,
        RequestInterface     $requestInterface,
        ContextHelper        $contextHelper,
        Curl                 $curl
    ) {
        $this->clerkLogger = $clerkLogger;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->requestInterface = $requestInterface;
        $this->contextHelper = $contextHelper;
        $this->curlClient = $curl;
    }

    /**
     * Add product
     *
     * @param array|object $product_data
     * @param int|string $store_id
     * @return void
     */
    public function addProduct($product_data, $store_id = null)
    {
        try {
            $params = [
                'products' => [$product_data],
            ];
            $this->post('product/add', $params, $store_id);
            $this->clerkLogger->log('Added Product', ['response' => $product_data]);

        } catch (Exception $e) {

            $this->clerkLogger->error('Adding Products Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Perform a POST request
     *
     * @param string $endpoint
     * @param array $params
     * @param string|void $store_id
     * @return string|void
     * @throws Exception
     */
    private function post($endpoint, $params = [], $store_id = null)
    {
        try {
            $params = array_merge($this->getDefaultParams($store_id), $params);
            $url = $this->baseurl . $endpoint;
            $this->curlClient->post($url, $params);
            return $this->curlClient->getBody();
        } catch (Exception $e) {
            $this->clerkLogger->error('POST Request Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get default request params
     *
     * @param string|int|void $store_id
     * @return array
     */
    private function getDefaultParams($store_id = null)
    {
        if (null === $store_id) {
            $scope = $this->contextHelper->getScope();
            $scope_id = $this->contextHelper->getScopeId();
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
     * Log basket
     *
     * @param array $product_ids
     * @param string $email
     * @param int|string|void $store_id
     * @throws Exception
     */
    public function logBasket($product_ids, $email, $store_id = null)
    {
        try {
            $params = [
                'products' => $product_ids,
                'email' => $email,
            ];
            $this->get('log/basket/set', $params, $store_id);
            $this->clerkLogger->log('Removed Product', ['response' => $params]);
        } catch (Exception $e) {
            $this->clerkLogger->error('Removing Products Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Perform a GET request
     *
     * @param string $endpoint
     * @param array $params
     * @param int|string $store_id
     * @return string|void
     * @throws Exception
     */
    private function get($endpoint, $params = [], $store_id = null)
    {
        try {

            $params = array_merge($this->getDefaultParams($store_id), $params);
            $url = $this->baseurl . $endpoint;
            if (!empty($params)) {
                $params = http_build_query($params);
                $url = $url . '?' . $params;
            }
            $this->curlClient->get($url);
            return $this->curlClient->getBody();

        } catch (Exception $e) {
            $this->clerkLogger->error('GET Request Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove product
     *
     * @param int|string $product_id
     * @param int|string|void $store_id
     * @throws Exception
     */
    public function removeProduct($product_id, $store_id = null)
    {
        try {
            $params = [
                'products' => [$product_id],
            ];
            $this->get('product/remove', $params, $store_id);
            $this->clerkLogger->log('Removed Product', ['response' => $params]);
        } catch (Exception $e) {
            $this->clerkLogger->error('Removing Products Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Return product api caller
     *
     * @param int|string $orderIncrementId
     * @param int|string $product_id
     * @param int $quantity
     * @param int|string $store_id
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
            $this->clerkLogger->log('Returned Product', ['response' => $params]);
        } catch (Exception $e) {
            $this->clerkLogger->error('Returning Products Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Validate public & private key
     *
     * @param string $publicKey
     * @param string $privateKey
     * @return string|void
     * @throws Exception
     */
    public function keysValid($publicKey, $privateKey)
    {
        try {
            $params = [
                'key' => $publicKey,
                'private_key' => $privateKey,
            ];
            return $this->get('client/account/info', $params);
        } catch (Exception $e) {
            $this->clerkLogger->error('Key Validation Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get available facet attributes
     *
     * @return array|object|void
     * @throws Exception
     */
    public function getFacetAttributes()
    {
        try {
            $facetAttributesResponse = $this->get('product/facets');
            if ($facetAttributesResponse) {
                return json_decode($facetAttributesResponse);
            }
        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Facet Attributes Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get endpoint for embedded content
     *
     * @param string $contentId
     * @return void|array|object
     */
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
        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Endpoint For Content Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get Clerk Content
     *
     * @param int $store_id
     * @return string|void
     * @throws Exception
     */
    public function getContent($store_id = null)
    {
        try {
            if (null === $store_id) {
                $scope = $this->contextHelper->getScope();
                $scope_id = $this->contextHelper->getScopeId();
            } else {
                $scope = 'store';
                $scope_id = $store_id;
            }
            $params = [
                'key' => $this->scopeConfig->getValue(Config::XML_PATH_PUBLIC_KEY, $scope, $scope_id),
                'private_key' => $this->scopeConfig->getValue(Config::XML_PATH_PRIVATE_KEY, $scope, $scope_id),
            ];
            return $this->get('client/account/content/list', $params);
        } catch (Exception $e) {
            $this->clerkLogger->error('Getting Content Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Validate token with Clerk
     *
     * @param string $token_string
     * @param string $publicKey
     * @throws Exception
     * @return array|void|bool
     */
    public function verifyToken($token_string = null, $publicKey = null)
    {
        if (!$token_string || !$publicKey) {
            return false;
        }

        try {
            $query_params = [
                'token' => $token_string,
                'key' => $publicKey,
            ];
            $url = $this->baseurl . 'token/verify';
            $response = $this->get($url, $query_params);
            $decodedResponse = json_decode($response, true);
            return (array) $decodedResponse;
        } catch (Exception $e) {

            $this->logger->error(' Communicator "postTokenVerification"', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Get mandatory parameters for endpoint
     *
     * @param string $endpoint
     * @return string[]
     */
    public function getParametersForEndpoint($endpoint)
    {
        $endpoint_map = [
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

        if (array_key_exists($endpoint, $endpoint_map)) {
            return $endpoint_map[$endpoint];
        }

        return ['limit'];
    }
}
