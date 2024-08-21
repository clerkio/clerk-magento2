<?php

namespace Clerk\Clerk\Model;

class Config
{
    /**
     * General configuration
     */
    public const XML_PATH_PRIVATE_KEY = 'clerk/general/private_key';
    public const XML_PATH_PUBLIC_KEY = 'clerk/general/public_key';
    public const XML_PATH_LANGUAGE = 'clerk/general/language';
    public const XML_PATH_INCLUDE_PAGES = 'clerk/general/include_pages';
    public const XML_PATH_USE_LEGACY_AUTH = 'clerk/general/legacy_auth';
    public const XML_PATH_PAGES_ADDITIONAL_FIELDS = 'clerk/general/pages_additional_fields';

    /**
     * Product Synchronization configuration
     */
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED =
        'clerk/product_synchronization/use_realtime_updates';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS = 'clerk/product_synchronization/collect_emails';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS = 'clerk/product_synchronization/collect_baskets';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS = 'clerk/product_synchronization/additional_fields';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS_HEAVY_QUERY =
        'clerk/product_synchronization/additional_fields_heavy_query';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY = 'clerk/product_synchronization/saleable_only';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY = 'clerk/product_synchronization/visibility';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION =
        'clerk/product_synchronization/disable_order_synchronization';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_IMAGE_TYPE = 'clerk/product_synchronization/image_type';
    public const XML_PATH_PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION =
        'clerk/product_synchronization/return_order_synchronization';
    /**
     * Customer Synchronization configuration
     */
    public const XML_PATH_CUSTOMER_SYNCHRONIZATION_ENABLED = 'clerk/customer_synchronization/enabled';
    public const XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES = 'clerk/customer_synchronization/extra_attributes';
    public const XML_PATH_SUBSCRIBER_SYNCHRONIZATION_ENABLED = 'clerk/customer_synchronization/sync_subscribers';
    /**
     * Search configuration
     */
    public const XML_PATH_SEARCH_ENABLED = 'clerk/search/enabled';
    public const XML_PATH_SEARCH_INCLUDE_CATEGORIES = 'clerk/search/include_categories';
    public const XML_PATH_SEARCH_CATEGORIES = 'clerk/search/categories';
    public const XML_PATH_SEARCH_SUGGESTIONS = 'clerk/search/suggestions';
    public const XML_PATH_SEARCH_PAGES = 'clerk/search/pages';
    public const XML_PATH_SEARCH_PAGES_TYPE = 'clerk/search/pages_type';
    public const XML_PATH_SEARCH_TEMPLATE = 'clerk/search/template';
    public const XML_PATH_SEARCH_NO_RESULTS_TEXT = 'clerk/search/no_results_text';
    public const XML_PATH_SEARCH_LOAD_MORE_TEXT = 'clerk/search/load_more_text';

    /**
     * Faceted Search configuration
     */
    public const XML_PATH_FACETED_SEARCH_ENABLED = 'clerk/faceted_search/enabled';
    public const XML_PATH_FACETED_SEARCH_DESIGN = 'clerk/faceted_search/design';
    public const XML_PATH_FACETED_SEARCH_ATTRIBUTES = 'clerk/faceted_search/attributes';
    public const XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES = 'clerk/faceted_search/multiselect_attributes';
    public const XML_PATH_FACETED_SEARCH_TITLES = 'clerk/faceted_search/titles';

    /**
     * Live search configuration
     */
    public const XML_PATH_LIVESEARCH_ENABLED = 'clerk/livesearch/enabled';
    public const XML_PATH_LIVESEARCH_INCLUDE_CATEGORIES = 'clerk/livesearch/include_categories';
    public const XML_PATH_LIVESEARCH_CATEGORIES = 'clerk/livesearch/categories';
    public const XML_PATH_LIVESEARCH_SUGGESTIONS = 'clerk/livesearch/suggestions';
    public const XML_PATH_LIVESEARCH_PAGES = 'clerk/livesearch/pages';
    public const XML_PATH_LIVESEARCH_PAGES_TYPE = 'clerk/livesearch/pages_type';
    public const XML_PATH_LIVESEARCH_DROPDOWN_POSITION = 'clerk/livesearch/dropdown_position';
    public const XML_PATH_LIVESEARCH_TEMPLATE = 'clerk/livesearch/template';
    public const XML_PATH_LIVESEARCH_INPUT_SELECTOR = 'clerk/livesearch/input_selector';
    public const XML_PATH_LIVESEARCH_FORM_SELECTOR = 'clerk/livesearch/form_selector';

    /**
     * Powerstep configuration
     */
    public const XML_PATH_POWERSTEP_ENABLED = 'clerk/powerstep/enabled';
    public const XML_PATH_POWERSTEP_TYPE = 'clerk/powerstep/type';
    public const XML_PATH_POWERSTEP_TEMPLATES = 'clerk/powerstep/templates';
    public const XML_PATH_POWERSTEP_FILTER_DUPLICATES = 'clerk/powerstep/powerstep_filter';

    /**
     * Exit intent configuration
     */
    public const XML_PATH_EXIT_INTENT_ENABLED = 'clerk/exit_intent/enabled';
    public const XML_PATH_EXIT_INTENT_TEMPLATE = 'clerk/exit_intent/template';

    /**
     * Category configuration
     */
    public const XML_PATH_CATEGORY_ENABLED = 'clerk/category/enabled';
    public const XML_PATH_CATEGORY_CONTENT = 'clerk/category/content';
    public const XML_PATH_CATEGORY_FILTER_DUPLICATES = 'clerk/category/category_filter';

    /**
     * Product configuration
     */
    public const XML_PATH_PRODUCT_ENABLED = 'clerk/product/enabled';
    public const XML_PATH_PRODUCT_CONTENT = 'clerk/product/content';
    public const XML_PATH_PRODUCT_FILTER_DUPLICATES = 'clerk/product/product_filter';

    /**
     * Cart configuration
     */
    public const XML_PATH_CART_ENABLED = 'clerk/cart/enabled';
    public const XML_PATH_CART_CONTENT = 'clerk/cart/content';
    public const XML_PATH_CART_FILTER_DUPLICATES = 'clerk/cart/cart_filter';

    /**
     * Logger configuration
     */
    public const XML_PATH_LOG_LEVEL = 'clerk/log/level';
    public const XML_PATH_LOG_TO = 'clerk/log/to';
    public const XML_PATH_LOG_ENABLED = 'clerk/log/enabled';
}
