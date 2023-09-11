<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Locale\Resolver;

class Language implements ArrayInterface
{
    /**
     * @var Resolver
     */
    protected $_store;

    /**
     * Language model constructor.
     *
     * @param Resolver $localeResolver
     */
    public function __construct(
        Resolver $localeResolver
        )
    {
        $this->_store = $localeResolver;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {

        $store = $this->_store;

        $Langs = [
            ['value' => 'danish', 'label' => 'Danish'],
            ['value' => 'dutch', 'label' => 'Dutch'],
            ['value' => 'english', 'label' => 'English'],
            ['value' => 'finnish', 'label' => 'Finnish'],
            ['value' => 'french', 'label' => 'French'],
            ['value' => 'german', 'label' => 'German'],
            ['value' => 'hungarian', 'label' => 'Hungarian'],
            ['value' => 'italian', 'label' => 'Italian'],
            ['value' => 'norwegian', 'label' => 'Norwegian'],
            ['value' => 'portuguese', 'label' => 'Portuguese'],
            ['value' => 'romanian', 'label' => 'Romanian'],
            ['value' => 'russian', 'label' => 'Russian'],
            ['value' => 'spanish', 'label' => 'Spanish'],
            ['value' => 'swedish', 'label' => 'Swedish'],
            ['value' => 'turkish', 'label' => 'Turkish']
        ];

        $locale = $store->getLocale();

        $LangsAuto = [
            'da_DK' => 'Danish',
            'nl_NL' => 'Dutch',
            'en_US' => 'English',
            'en_GB' => 'English',
            'fi' => 'Finnish',
            'fr_FR' => 'French',
            'fr_BE' => 'French',
            'de_DE' => 'German',
            'hu_HU' => 'Hungarian',
            'it_IT' => 'Italian',
            'nn_NO' => 'Norwegian',
            'nb_NO' => 'Norwegian',
            'pt_PT' => 'Portuguese',
            'pt_BR' => 'Portuguese',
            'ro_RO' => 'Romanian',
            'ru_RU' => 'Russian',
            'ru_UA' => 'Russian',
            'es_ES' => 'Spanish',
            'sv_SE' => 'Swedish',
            'tr_TR' => 'Turkish'
        ];

        if (isset($LangsAuto[$locale])) {

            $AutoLang = ['label' => sprintf('Auto (%s)', $LangsAuto[$locale]), 'value' => 'auto_'.strtolower($LangsAuto[$locale])];

        }

        if (isset($AutoLang)) {

            array_unshift($Langs, $AutoLang);

        }

        return $Langs;
    }
}
