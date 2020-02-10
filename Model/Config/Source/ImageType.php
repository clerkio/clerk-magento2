<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Catalog\Helper\Image;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\View\ConfigInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;
use mysql_xdevapi\Exception;

class ImageType implements ArrayInterface
{
    /**
     * @var ConfigInterface
     */
    protected $viewConfig;

    /**
     * @var CollectionFactory
     */
    protected $themeCollectionFactory;


    public function __construct(
        ConfigInterface $viewConfig,
        CollectionFactory $themeCollectionFactory
    )
    {
        $this->viewConfig = $viewConfig;
        $this->themeCollectionFactory = $themeCollectionFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $types = [];

        /** @var Collection $themes */
        $themes = $this->themeCollectionFactory->create();
        $themes->addAreaFilter('frontend');

        $common = [];

        /** @var ThemeInterface $theme */
        foreach ($themes as $theme) {

            try {

                $config = $this->viewConfig->getViewConfig([
                    'themeModel' => $theme,
                ]);
                $mediaEntities = $config->getMediaEntities('Magento_Catalog', Image::MEDIA_TYPE_CONFIG_NODE);
                $types[$theme->getCode()] = $mediaEntities;

                $common = $common ? array_intersect_key($common, $types[$theme->getCode()]) : $types[$theme->getCode()];

            }catch(\Exception $e) {

                continue;

            }
        }

        foreach ($types as $theme => $mediaEntities) {
            $types[$theme] = array_diff_key($mediaEntities, $common);
        }

        //Add common in beginning
        $types = ['Common' => $common] + $types;

        //Add default
        $result = ['' => __('Original image (full size)')];

        foreach ($types as $theme => $mediaEntities) {
            $result[$theme] = [
                'label' => $theme,
                'value' => [],
            ];
            foreach ($mediaEntities as $type => $entity) {
                $label = $type;

                if (isset($entity['width']) && isset($entity['height'])) {
                    $label .= " (" . $entity['width'] . "x" . $entity['height'] . ")";
                }

                $result[$theme]['value'][] = [
                    'label' => $label,
                    'value' => $type,
                ];
            }
        }

        return $result;
    }
}