<?php

namespace Clerk\Clerk\Model;

class Config
{
    /**
     * General configuration
     */
    const XML_PATH_PRIVATE_KEY = 'clerk/general/private_key';
    const XML_PATH_PUBLIC_KEY = 'clerk/general/public_key';

    /**
     * Product Synchronization configuration
     */
    const XML_PATH_PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED = 'clerk/product_synchronization/use_realtime_updates';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS = 'clerk/product_synchronization/collect_emails';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS = 'clerk/product_synchronization/additional_fields';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_SALABLE_ONLY = 'clerk/product_synchronization/saleable_only';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_VISIBILITY = 'clerk/product_synchronization/visibility';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION = 'clerk/product_synchronization/disable_order_synchronization';
    const XML_PATH_PRODUCT_SYNCHRONIZATION_IMAGE_TYPE = 'clerk/product_synchronization/image_type';

    /**
     * Search configuration
     */
    const XML_PATH_SEARCH_ENABLED = 'clerk/search/enabled';
    const XML_PATH_SEARCH_TEMPLATE = 'clerk/search/template';

    /**
     * Faceted Search configuration
     */
    const XML_PATH_FACETED_SEARCH_ENABLED = 'clerk/faceted_search/enabled';
    const XML_PATH_FACETED_SEARCH_ATTRIBUTES = 'clerk/faceted_search/attributes';
    const XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES = 'clerk/faceted_search/multiselect_attributes';
    const XML_PATH_FACETED_SEARCH_TITLES = 'clerk/faceted_search/titles';

    /**
     * Live search configuration
     */
    const XML_PATH_LIVESEARCH_ENABLED = 'clerk/livesearch/enabled';
    const XML_PATH_LIVESEARCH_INCLUDE_CATEGORIES = 'clerk/livesearch/include_categories';
    const XML_PATH_LIVESEARCH_TEMPLATE = 'clerk/livesearch/template';

    /**
     * Powerstep configuration
     */
    const XML_PATH_POWERSTEP_ENABLED = 'clerk/powerstep/enabled';
    const XML_PATH_POWERSTEP_TYPE = 'clerk/powerstep/type';
    const XML_PATH_POWERSTEP_TEMPLATES = 'clerk/powerstep/templates';

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

    /**
     * Product configuration
     */
    const XML_PATH_PRODUCT_ENABLED = 'clerk/product/enabled';
    const XML_PATH_PRODUCT_CONTENT = 'clerk/product/content';

    /**
     * Cart configuration
     */
    const XML_PATH_CART_ENABLED = 'clerk/cart/enabled';
    const XML_PATH_CART_CONTENT = 'clerk/cart/content';
}