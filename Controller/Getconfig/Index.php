<?php

namespace Clerk\Clerk\Controller\Getconfig;

use Clerk\Clerk\Controller\AbstractAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleList;
use Psr\Log\LoggerInterface;
use Clerk\Clerk\Controller\Logger\ClerkLogger;
use Magento\Store\Model\ScopeInterface;
use Clerk\Clerk\Model\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Webapi\Rest\Request as RequestApi;

class Index extends AbstractAction
{
    /**
     * @var ClerkLogger
     */
    protected $clerk_logger;

    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Version controller constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param ModuleList $moduleList
     * @param ProductMetadataInterface $product_metadata
     * @param RequestApi $request_api
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ModuleList $moduleList,
        StoreManagerInterface $storeManager,
        ClerkLogger $clerk_logger,
        ProductMetadataInterface $product_metadata,
        RequestApi $request_api
        )
    {
        $this->moduleList = $moduleList;
        $this->clerk_logger = $clerk_logger;
        $this->store_manager = $storeManager;
        parent::__construct(
            $context,
            $storeManager,
            $scopeConfig,
            $logger,
            $moduleList,
            $clerk_logger,
            $product_metadata,
            $request_api
        );
    }

    /**
     * Execute request
     */
    public function execute()
    {
        try {

            $scope = $this->getRequest()->getParam('scope');

            $scopeID = 1;

            if (null !== $this->getRequest()->getParam('scope_id')) {
                $scopeID = $this->getRequest()->getParam('scope_id');
            }

            if ($scope == 'store') {
                $storeID = $scopeID;
                $websiteID = null;
            } elseif ($scope == 'website') {
                $websiteID = $scopeID;
                $storeID = null;
            } elseif ($scope == 'default') {
                $websiteID = $scopeID;
                $storeID = $scopeID;
            }

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            $response = [
                'scopeNAME' => $scope,
                'storeID' => $storeID,
                'wepsiteID' => $websiteID,
                'LANGUAGE' => $this->scopeConfig->getValue(Config::XML_PATH_LANGUAGE, $scope, $scopeID),
                'PATH_INCLUDE_PAGES' => $this->scopeConfig->getValue(Config::XML_PATH_INCLUDE_PAGES, $scope, $scopeID),
                'PAGES_ADDITIONAL_FIELDS' => $this->scopeConfig->getValue(Config::XML_PATH_PAGES_ADDITIONAL_FIELDS, $scope, $scopeID),

                'PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED, $scope, $scopeID),
                'PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS, $scope, $scopeID),
                'PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS, $scope, $scopeID),
                'PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS, $scope, $scopeID),
                'PRODUCT_SYNCHRONIZATION_SALABLE_ONLY' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY, $scope, $scopeID),
                'PRODUCT_SYNCHRONIZATION_VISIBILITY' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY, $scope, $scopeID),
                'PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION, $scope, $scopeID),
                'PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION, $scope, $scopeID),
                'PRODUCT_SYNCHRONIZATION_IMAGE_TYPE' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_SYNCHRONIZATION_IMAGE_TYPE, $scope, $scopeID),

                'CUSTOMER_SYNCHRONIZATION_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_ENABLED, $scope, $scopeID),
                'CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES' => $this->scopeConfig->getValue(Config::XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES, $scope, $scopeID),

                'SEARCH_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_SEARCH_ENABLED, $scope, $scopeID),
                'SEARCH_INCLUDE_CATEGORIES' => $this->scopeConfig->getValue(Config::XML_PATH_SEARCH_INCLUDE_CATEGORIES, $scope, $scopeID),
                'SEARCH_CATEGORIES' => $this->scopeConfig->getValue(Config::XML_PATH_SEARCH_CATEGORIES, $scope, $scopeID),
                'SEARCH_PAGES' => $this->scopeConfig->getValue(Config::XML_PATH_SEARCH_PAGES, $scope, $scopeID),
                'SEARCH_PAGES_TYPE' => $this->scopeConfig->getValue(Config::XML_PATH_SEARCH_PAGES_TYPE, $scope, $scopeID),
                'SEARCH_TEMPLATE' => $this->scopeConfig->getValue(Config::XML_PATH_SEARCH_TEMPLATE, $scope, $scopeID),
                'SEARCH_NO_RESULTS_TEXT' => $this->scopeConfig->getValue(Config::XML_PATH_SEARCH_NO_RESULTS_TEXT, $scope, $scopeID),
                'SEARCH_LOAD_MORE_TEXT' => $this->scopeConfig->getValue(Config::XML_PATH_SEARCH_LOAD_MORE_TEXT, $scope, $scopeID),

                'FACETED_SEARCH_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ENABLED, $scope, $scopeID),
                'FACETED_SEARCH_DESIGN' => $this->scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_DESIGN, $scope, $scopeID),
                'FACETED_SEARCH_ATTRIBUTES' => $this->scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, $scope, $scopeID),
                'FACETED_SEARCH_MULTISELECT_ATTRIBUTES' => $this->scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES, $scope, $scopeID),
                'FACETED_SEARCH_TITLES' => $this->scopeConfig->getValue(Config::XML_PATH_FACETED_SEARCH_TITLES, $scope, $scopeID),

                'LIVESEARCH_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_ENABLED, $scope, $scopeID),
                'LIVESEARCH_INCLUDE_CATEGORIES' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_INCLUDE_CATEGORIES, $scope, $scopeID),
                'LIVESEARCH_CATEGORIES' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_CATEGORIES, $scope, $scopeID),
                'LIVESEARCH_SUGGESTIONS' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_SUGGESTIONS, $scope, $scopeID),
                'LIVESEARCH_PAGES' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_PAGES, $scope, $scopeID),
                'LIVESEARCH_PAGES_TYPE' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_PAGES_TYPE, $scope, $scopeID),
                'LIVESEARCH_DROPDOWN_POSITION' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_DROPDOWN_POSITION, $scope, $scopeID),
                'LIVESEARCH_TEMPLATE' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_TEMPLATE, $scope, $scopeID),
                'LIVESEARCH_INPUT_SELECTOR' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_INPUT_SELECTOR, $scope, $scopeID),
                'LIVESEARCH_FORM_SELECTOR' => $this->scopeConfig->getValue(Config::XML_PATH_LIVESEARCH_FORM_SELECTOR, $scope, $scopeID),

                'POWERSTEP_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_POWERSTEP_ENABLED, $scope, $scopeID),
                'POWERSTEP_TYPE' => $this->scopeConfig->getValue(Config::XML_PATH_POWERSTEP_TYPE, $scope, $scopeID),
                'POWERSTEP_TEMPLATES' => $this->scopeConfig->getValue(Config::XML_PATH_POWERSTEP_TEMPLATES, $scope, $scopeID),
                'POWERSTEP_FILTER_DUPLICATES' => $this->scopeConfig->getValue(Config::XML_PATH_POWERSTEP_FILTER_DUPLICATES, $scope, $scopeID),

                'EXIT_INTENT_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_EXIT_INTENT_ENABLED, $scope, $scopeID),
                'EXIT_INTENT_TEMPLATE' => $this->scopeConfig->getValue(Config::XML_PATH_EXIT_INTENT_TEMPLATE, $scope, $scopeID),

                'CATEGORY_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_CATEGORY_ENABLED, $scope, $scopeID),
                'CATEGORY_CONTENT' => $this->scopeConfig->getValue(Config::XML_PATH_CATEGORY_CONTENT, $scope, $scopeID),
                'CATEGORY_FILTER_DUPLICATES' => $this->scopeConfig->getValue(Config::XML_PATH_CATEGORY_FILTER_DUPLICATES, $scope, $scopeID),

                'PRODUCT_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_ENABLED, $scope, $scopeID),
                'PRODUCT_CONTENT' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_CONTENT, $scope, $scopeID),
                'PRODUCT_FILTER_DUPLICATES' => $this->scopeConfig->getValue(Config::XML_PATH_PRODUCT_FILTER_DUPLICATES, $scope, $scopeID),

                'CART_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_CART_ENABLED, $scope, $scopeID),
                'CART_CONTENT' => $this->scopeConfig->getValue(Config::XML_PATH_CART_CONTENT, $scope, $scopeID),
                'CART_FILTER_DUPLICATES' => $this->scopeConfig->getValue(Config::XML_PATH_CART_FILTER_DUPLICATES, $scope, $scopeID),

                'LOG_LEVEL' => $this->scopeConfig->getValue(Config::XML_PATH_LOG_LEVEL, $scope, $scopeID),
                'LOG_TO' => $this->scopeConfig->getValue(Config::XML_PATH_LOG_TO, $scope, $scopeID),
                'LOG_ENABLED' => $this->scopeConfig->getValue(Config::XML_PATH_LOG_ENABLED, $scope, $scopeID)

            ];

            if ($this->debug) {
                $this->getResponse()->setBody(json_encode($response, JSON_PRETTY_PRINT));
            } else {
                $this->getResponse()->setBody(json_encode($response));
            }
        } catch (\Exception $e) {

            $this->clerk_logger->error('Getconfig execute ERROR', ['error' => $e->getMessage()]);

        }
    }
}
