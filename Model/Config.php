<?php

namespace Clerk\Clerk\Model;

class Config
{
    /**
     * General configuration
     */
    const XML_PATH_PRIVATE_KEY = 'clerk/general/private_key';
    const XML_PATH_PUBLIC_KEY = 'clerk/general/public_key';
    const XML_PATH_LANGUAGE = 'clerk/general/language';
    const XML_PATH_INCLUDE_PAGES = 'clerk/general/include_pages';
    const XML_PATH_USE_LEGACY_AUTH = 'clerk/general/legacy_auth';
    const XML_PATH_PAGES_ADDITIONAL_FIELDS = 'clerk/general/pages_additional_fields';


    /**
     * Product Synchronization configuration
     */
    const XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED = 'clerk/product_synchronization/use_realtime_updates';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS = 'clerk/product_synchronization/collect_emails';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS = 'clerk/product_synchronization/collect_baskets';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS = 'clerk/product_synchronization/additional_fields';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS_HEAVY_QUERY = 'clerk/product_synchronization/additional_fields_heavy_query';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY = 'clerk/product_synchronization/saleable_only';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY = 'clerk/product_synchronization/visibility';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION = 'clerk/product_synchronization/disable_order_synchronization';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_IMAGE_TYPE = 'clerk/product_synchronization/image_type';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION = 'clerk/product_synchronization/return_order_synchronization';
    /**
     * Customer Synchronization configuration
     */
    const XML_PATH_CUSTOMER_SYNCHRONIZATION_ENABLED = 'clerk/customer_synchronization/enabled';
    const XML_PATH_CUSTOMER_SYNCHRONIZATION_EXTRA_ATTRIBUTES = 'clerk/customer_synchronization/extra_attributes';
    const XML_PATH_SUBSCRIBER_SYNCHRONIZATION_ENABLED = 'clerk/customer_synchronization/sync_subscribers';
    /**
     * Search configuration
     */
    const XML_PATH_SEARCH_ENABLED = 'clerk/search/enabled';
    const XML_PATH_SEARCH_INCLUDE_CATEGORIES = 'clerk/search/include_categories';
    const XML_PATH_SEARCH_CATEGORIES = 'clerk/search/categories';
    const XML_PATH_SEARCH_SUGGESTIONS = 'clerk/search/suggestions';
    const XML_PATH_SEARCH_PAGES = 'clerk/search/pages';
    const XML_PATH_SEARCH_PAGES_TYPE = 'clerk/search/pages_type';
    const XML_PATH_SEARCH_TEMPLATE = 'clerk/search/template';
    const XML_PATH_SEARCH_NO_RESULTS_TEXT = 'clerk/search/no_results_text';
    const XML_PATH_SEARCH_LOAD_MORE_TEXT = 'clerk/search/load_more_text';

    /**
     * Faceted Search configuration
     */
    const XML_PATH_FACETED_SEARCH_ENABLED = 'clerk/faceted_search/enabled';
    const XML_PATH_FACETS_IN_URL = 'clerk/faceted_search/facets_in_url';
    const XML_PATH_FACETED_SEARCH_DESIGN = 'clerk/faceted_search/design';
    const XML_PATH_FACETED_SEARCH_ATTRIBUTES = 'clerk/faceted_search/attributes';
    const XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES = 'clerk/faceted_search/multiselect_attributes';
    const XML_PATH_FACETED_SEARCH_TITLES = 'clerk/faceted_search/titles';

    /**
     * Live search configuration
     */
    const XML_PATH_LIVESEARCH_ENABLED = 'clerk/livesearch/enabled';
    const XML_PATH_LIVESEARCH_INCLUDE_CATEGORIES = 'clerk/livesearch/include_categories';
    const XML_PATH_LIVESEARCH_CATEGORIES = 'clerk/livesearch/categories';
    const XML_PATH_LIVESEARCH_SUGGESTIONS = 'clerk/livesearch/suggestions';
    const XML_PATH_LIVESEARCH_PAGES = 'clerk/livesearch/pages';
    const XML_PATH_LIVESEARCH_PAGES_TYPE = 'clerk/livesearch/pages_type';
    const XML_PATH_LIVESEARCH_DROPDOWN_POSITION = 'clerk/livesearch/dropdown_position';
    const XML_PATH_LIVESEARCH_TEMPLATE = 'clerk/livesearch/template';
    const XML_PATH_LIVESEARCH_INPUT_SELECTOR = 'clerk/livesearch/input_selector';
    const XML_PATH_LIVESEARCH_FORM_SELECTOR = 'clerk/livesearch/form_selector';

    /**
     * Powerstep configuration
     */
    const XML_PATH_POWERSTEP_ENABLED = 'clerk/powerstep/enabled';
    const XML_PATH_POWERSTEP_TYPE = 'clerk/powerstep/type';
    const XML_PATH_POWERSTEP_TEMPLATES = 'clerk/powerstep/templates';
    const XML_PATH_POWERSTEP_FILTER_DUPLICATES = 'clerk/powerstep/powerstep_filter';

    /**
     * Exit intent configuration
     */
    const XML_PATH_EXIT_INTENT_ENABLED = 'clerk/exit_intent/enabled';
    const XML_PATH_EXIT_INTENT_TEMPLATE = 'clerk/exit_intent/template';

    /**
     * Category configuration
     */
    const XML_PATH_CATEGORY_ENABLED = 'clerk/category/enabled';
    const XML_PATH_CATEGORY_CONTENT = 'clerk/category/content';
    const XML_PATH_CATEGORY_FILTER_DUPLICATES = 'clerk/category/category_filter';

    /**
     * Product configuration
     */
    const XML_PATH_PRODUCT_ENABLED = 'clerk/product/enabled';
    const XML_PATH_PRODUCT_CONTENT = 'clerk/product/content';
    const XML_PATH_PRODUCT_FILTER_DUPLICATES = 'clerk/product/product_filter';

    /**
     * Cart configuration
     */
    const XML_PATH_CART_ENABLED = 'clerk/cart/enabled';
    const XML_PATH_CART_CONTENT = 'clerk/cart/content';
    const XML_PATH_CART_FILTER_DUPLICATES = 'clerk/cart/cart_filter';

    /**
     * Logger configuration
     */
    const XML_PATH_LOG_LEVEL = 'clerk/log/level';
    const XML_PATH_LOG_TO = 'clerk/log/to';
    const XML_PATH_LOG_ENABLED = 'clerk/log/enabled';
}
