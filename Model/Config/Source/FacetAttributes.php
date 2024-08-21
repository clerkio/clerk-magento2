<?php

namespace Clerk\Clerk\Model\Config\Source;

use Clerk\Clerk\Model\Api;
use Exception;
use Magento\Framework\Option\ArrayInterface;

class FacetAttributes implements ArrayInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * FacetAttributes constructor.
     *
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $res = [];

        try {
            foreach (self::toArray() as $_ => $value) {
                $res[] = [
                    'value' => $value,
                    'label' => $value
                ];
            }
        } catch (Exception $e) {
            return $res;
        }

        return $res;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     * @throws Exception
     */
    public function toArray()
    {
        $attributes = $this->getFacetAttributes();
        $values = [];
        foreach ($attributes as $attribute => $facet) {
            $values[$attribute] = $attribute;
        }
        return $values;
    }

    /**
     * Get facet attributes from Clerk API
     *
     * @return array|mixed
     * @throws Exception
     */
    private function getFacetAttributes()
    {
        $attributes = $this->api->getFacetAttributes();
        if ($attributes && isset($attributes->facets)) {
            return $attributes->facets;
        }
        return [];
    }
}
