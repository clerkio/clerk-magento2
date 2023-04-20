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

            $params = [
                'products' => [$productId],
            ];

            $this->get('product/remove', $params, $store_id);
            $this->clerk_logger->log('Removed Product', ['response' => $params]);

        } catch (\Exception $e) {

            $this->clerk_logger->error('Removing Products Error', ['error' => $e->getMessage()]);

        }
    }

    private function _curl_get($url, $params = [])
    {
        try {

            if (!empty($params)) {
                $params = is_array($params) ? http_build_query($params) : $params;
                $url = $url . '?' . $params;
            }
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;

        } catch (\Exception $e) {

            $this->clerk_logger->error('GET Request Error', ['error' => $e->getMessage()]);

        }
    }

    private function _curl_post($url, $params = [])
    {
        try {

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_POST, true);
            if (!empty($params)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
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

            $url = $this->baseurl . $endpoint;

            $response = $this->_curl_get($url, $params);

            return $response;

        } catch (\Exception $e) {

            $this->clerk_logger->error('GET Request Error', ['error' => $e->getMessage()]);

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
            'recommendations/category/popular_subcategories' => [
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