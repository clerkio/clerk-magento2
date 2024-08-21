<?php

namespace Clerk\Clerk\Model\Config\Source;

use Clerk\Clerk\Model\Api;
use Exception;
use Magento\Framework\Option\ArrayInterface;

class Content implements ArrayInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * Return array of clerk contents
     *
     * @return array
     */
    public function toOptionArray()
    {
        $contents = [];

        try {
            $contentsResponse = $this->api->getContent();

            if ($contentsResponse) {
                $contentsResponse = json_decode($contentsResponse);

                foreach ($contentsResponse->contents as $content) {
                    if ($content->type !== 'html') {
                        continue;
                    }

                    $contents[] = [
                        'value' => $content->id,
                        'label' => $content->name
                    ];
                }
            }
        } catch (Exception $e) {
            $contents[] = [
                'value' => '',
                'label' => 'No content found'
            ];
        }

        return $contents;
    }
}
