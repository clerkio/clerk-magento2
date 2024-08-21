<?php

namespace Clerk\Clerk\Model\Config\Source;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Option\ArrayInterface;

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
    ) {
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

        $languages = [
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

        $langs_auto = [
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

        if (isset($langs_auto[$locale])) {

            $auto_lang = ['label' => sprintf('Auto (%s)', $langs_auto[$locale]), 'value' => 'auto_'.strtolower($langs_auto[$locale])];

        }

        if (isset($auto_lang)) {

            array_unshift($languages, $auto_lang);

        }

        return $languages;
    }
}
