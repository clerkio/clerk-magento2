<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Catalog\Helper\Image;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\View\ConfigInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;

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

    /**
     * @param ConfigInterface $viewConfig
     * @param CollectionFactory $themeCollectionFactory
     */
    public function __construct(
        ConfigInterface $viewConfig,
        CollectionFactory $themeCollectionFactory
    ) {
        $this->viewConfig = $viewConfig;
        $this->themeCollectionFactory = $themeCollectionFactory;
    }

    /**
     * Finds image types for each frontend theme
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
            $config = $this->viewConfig->getViewConfig([
                'themeModel' => $theme,
            ]);
            $mediaEntities = $config->getMediaEntities('Magento_Catalog', Image::MEDIA_TYPE_CONFIG_NODE);
            $types[$theme->getCode()] = array_keys($mediaEntities);

            $common = $common ? array_intersect($common, $types[$theme->getCode()]) : $types[$theme->getCode()];
        }

        foreach ($types as $theme => $mediaEntities) {
            $types[$theme] = array_diff($mediaEntities, $common);
        }
        // add common in beginning
        $types = ['Common' => $common] + $types;

        $result = ['' => __('Original image')];
        foreach ($types as $theme => $mediaEntities) {
            $result[$theme] = [
                'label' => $theme,
                'value' => [],
            ];
            foreach ($mediaEntities as $type) {
                $result[$theme]['value'][] = [
                    'label' => $type,
                    'value' => $type,
                ];
            }
        }

        return $result;
    }
}
