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
            ['value' => 'afrikaans', 'label' => 'Afrikaans'],
            ['value' => 'albanian', 'label' => 'Albanian'],
            ['value' => 'arabic', 'label' => 'Arabic'],
            ['value' => 'armenian', 'label' => 'Armenian'],
            ['value' => 'azerbaijani', 'label' => 'Azerbaijani'],
            ['value' => 'basque', 'label' => 'Basque'],
            ['value' => 'belarusian', 'label' => 'Belarusian'],
            ['value' => 'bengali', 'label' => 'Bengali'],
            ['value' => 'bulgarian', 'label' => 'Bulgarian'],
            ['value' => 'catalan', 'label' => 'Catalan'],
            ['value' => 'chinese', 'label' => 'Chinese'],
            ['value' => 'croatian', 'label' => 'Croatian'],
            ['value' => 'czech', 'label' => 'Czech'],
            ['value' => 'danish', 'label' => 'Danish'],
            ['value' => 'dutch', 'label' => 'Dutch'],
            ['value' => 'english', 'label' => 'English'],
            ['value' => 'estonian', 'label' => 'Estonian'],
            ['value' => 'finnish', 'label' => 'Finnish'],
            ['value' => 'french', 'label' => 'French'],
            ['value' => 'georgian', 'label' => 'Georgian'],
            ['value' => 'german', 'label' => 'German'],
            ['value' => 'greek', 'label' => 'Greek'],
            ['value' => 'hebrew', 'label' => 'Hebrew'],
            ['value' => 'hindi', 'label' => 'Hindi'],
            ['value' => 'hungarian', 'label' => 'Hungarian'],
            ['value' => 'indonesian', 'label' => 'Indonesian'],
            ['value' => 'irish', 'label' => 'Irish'],
            ['value' => 'italian', 'label' => 'Italian'],
            ['value' => 'japanese', 'label' => 'Japanese'],
            ['value' => 'khmer', 'label' => 'Khmer'],
            ['value' => 'korean', 'label' => 'Korean'],
            ['value' => 'latvian', 'label' => 'Latvian'],
            ['value' => 'lithuanian', 'label' => 'Lithuanian'],
            ['value' => 'malay', 'label' => 'Malay'],
            ['value' => 'nepali', 'label' => 'Nepali'],
            ['value' => 'norwegian', 'label' => 'Norwegian'],
            ['value' => 'persian', 'label' => 'Persian'],
            ['value' => 'polish', 'label' => 'Polish'],
            ['value' => 'portuguese', 'label' => 'Portuguese'],
            ['value' => 'romanian', 'label' => 'Romanian'],
            ['value' => 'russian', 'label' => 'Russian'],
            ['value' => 'serbian', 'label' => 'Serbian'],
            ['value' => 'slovak', 'label' => 'Slovak'],
            ['value' => 'slovenian', 'label' => 'Slovenian'],
            ['value' => 'spanish', 'label' => 'Spanish'],
            ['value' => 'swahili', 'label' => 'Swahili'],
            ['value' => 'swedish', 'label' => 'Swedish'],
            ['value' => 'tamil', 'label' => 'Tamil'],
            ['value' => 'telugu', 'label' => 'Telugu'],
            ['value' => 'thai', 'label' => 'Thai'],
            ['value' => 'turkish', 'label' => 'Turkish'],
            ['value' => 'ukrainian', 'label' => 'Ukrainian'],
            ['value' => 'urdu', 'label' => 'Urdu'],
            ['value' => 'vietnamese', 'label' => 'Vietnamese'],
        ];

        $locale = $store->getLocale();

        $LangsAuto = [
            'af_ZA' => 'Afrikaans',
            'sq_AL' => 'Albanian',
            'ar_DZ' => 'Arabic',
            'ar_EG' => 'Arabic',
            'ar_SA' => 'Arabic',
            'hy_AM' => 'Armenian',
            'az_AZ' => 'Azerbaijani',
            'eu_ES' => 'Basque',
            'be_BY' => 'Belarusian',
            'bn_BD' => 'Bengali',
            'bg_BG' => 'Bulgarian',
            'ca_ES' => 'Catalan',
            'zh_Hans_CN' => 'Chinese',
            'zh_Hant_TW' => 'Chinese',
            'hr_HR' => 'Croatian',
            'cs_CZ' => 'Czech',
            'da_DK' => 'Danish',
            'nl_NL' => 'Dutch',
            'nl_BE' => 'Dutch',
            'en_US' => 'English',
            'en_GB' => 'English',
            'et_EE' => 'Estonian',
            'fi_FI' => 'Finnish',
            'fr_FR' => 'French',
            'fr_BE' => 'French',
            'ka_GE' => 'Georgian',
            'de_DE' => 'German',
            'de_AT' => 'German',
            'de_CH' => 'German',
            'el_GR' => 'Greek',
            'he_IL' => 'Hebrew',
            'hi_IN' => 'Hindi',
            'hu_HU' => 'Hungarian',
            'id_ID' => 'Indonesian',
            'ga_IE' => 'Irish',
            'it_IT' => 'Italian',
            'ja_JP' => 'Japanese',
            'km_KH' => 'Khmer',
            'ko_KR' => 'Korean',
            'lv_LV' => 'Latvian',
            'lt_LT' => 'Lithuanian',
            'ms_MY' => 'Malay',
            'ne_NP' => 'Nepali',
            'nn_NO' => 'Norwegian',
            'nb_NO' => 'Norwegian',
            'fa_IR' => 'Persian',
            'pl_PL' => 'Polish',
            'pt_PT' => 'Portuguese',
            'pt_BR' => 'Portuguese',
            'ro_RO' => 'Romanian',
            'ru_RU' => 'Russian',
            'ru_UA' => 'Russian',
            'sr_RS' => 'Serbian',
            'sk_SK' => 'Slovak',
            'sl_SI' => 'Slovenian',
            'es_ES' => 'Spanish',
            'es_MX' => 'Spanish',
            'sw_KE' => 'Swahili',
            'sv_SE' => 'Swedish',
            'ta_IN' => 'Tamil',
            'te_IN' => 'Telugu',
            'th_TH' => 'Thai',
            'tr_TR' => 'Turkish',
            'uk_UA' => 'Ukrainian',
            'ur_PK' => 'Urdu',
            'vi_VN' => 'Vietnamese',
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
