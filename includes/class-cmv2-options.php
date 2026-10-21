<?php
/**
 * CMV2 Options Class
 * Single source of truth for defaults, translations, regions and option lookup
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMV2_Options
{
    /** @var array|null Request-level cache of the merged options */
    private static $cache = null;

    /**
     * Default EEA/GDPR country list
     */
    public static function default_regions()
    {
        return ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU',
                'IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES',
                'SE','GB','IS','LI','NO','CH'];
    }

    /**
     * Region list used for both the GCM default and the EEA-only banner check.
     * Uses custom_regions if set, otherwise the default EEA/GDPR list.
     */
    public static function regions($opts = null)
    {
        $opts = $opts ?: self::get();
        $raw = isset($opts['custom_regions']) ? trim($opts['custom_regions']) : '';

        if ($raw !== '') {
            $regions = [];
            foreach (preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $part) {
                $code = strtoupper(trim($part));
                if (preg_match('/^[A-Z]{2}$/', $code)) {
                    $regions[] = $code;
                }
            }
            if (!empty($regions)) {
                return array_values(array_unique($regions));
            }
        }

        return self::default_regions();
    }

    /**
     * Banner texts per language
     */
    public static function translations()
    {
        return [
            'hu' => [
                'title' => 'Sütibeállítások',
                'description' => 'Az oldal sütiket és hasonló technológiákat használ a működéshez, statisztikához és hirdetésekhez. A nem feltétlenül szükséges elemeket csak az engedélyed után aktiváljuk.',
                'privacy_link_text' => 'Adatkezelési tájékoztató',
                'necessary_label' => 'Szükséges',
                'analytics_label' => 'Analitika (GA4)',
                'ads_label' => 'Hirdetés & Marketing (Google Ads, Meta)',
                'accept_all_text' => 'Elfogadok mindent',
                'reject_all_text' => 'Csak szükséges',
                'save_text' => 'Mentés',
                'open_button_text' => 'Sütibeállítások',
                'customize_text' => 'Testreszabás',
            ],
            'en' => [
                'title' => 'Cookie Settings',
                'description' => 'This website uses cookies and similar technologies for functionality, statistics, and advertising. Non-essential elements are only activated after your consent.',
                'privacy_link_text' => 'Privacy Policy',
                'necessary_label' => 'Necessary',
                'analytics_label' => 'Analytics (GA4)',
                'ads_label' => 'Advertising & Marketing (Google Ads, Meta)',
                'accept_all_text' => 'Accept All',
                'reject_all_text' => 'Necessary Only',
                'save_text' => 'Save',
                'open_button_text' => 'Cookie Settings',
                'customize_text' => 'Customize',
            ],
            'fr' => [
                'title' => 'Paramètres des cookies',
                'description' => 'Ce site utilise des cookies et des technologies similaires pour le fonctionnement, les statistiques et la publicité. Les éléments non essentiels ne sont activés qu\'avec votre consentement.',
                'privacy_link_text' => 'Politique de confidentialité',
                'necessary_label' => 'Nécessaire',
                'analytics_label' => 'Analytique (GA4)',
                'ads_label' => 'Publicité & Marketing (Google Ads, Meta)',
                'accept_all_text' => 'Tout accepter',
                'reject_all_text' => 'Nécessaire uniquement',
                'save_text' => 'Enregistrer',
                'open_button_text' => 'Paramètres des cookies',
                'customize_text' => 'Personnaliser',
            ],
            'no' => [
                'title' => 'Informasjonskapsler',
                'description' => 'Dette nettstedet bruker informasjonskapsler og lignende teknologier for funksjonalitet, statistikk og annonsering. Ikke-nødvendige elementer aktiveres kun etter ditt samtykke.',
                'privacy_link_text' => 'Personvernpolicy',
                'necessary_label' => 'Nødvendig',
                'analytics_label' => 'Analyse (GA4)',
                'ads_label' => 'Annonsering & Markedsføring (Google Ads, Meta)',
                'accept_all_text' => 'Godta alle',
                'reject_all_text' => 'Kun nødvendig',
                'save_text' => 'Lagre',
                'open_button_text' => 'Informasjonskapsler',
                'customize_text' => 'Tilpass',
            ],
        ];
    }

    /**
     * Non-text defaults (texts come from the translations)
     */
    public static function defaults()
    {
        return array_merge(self::translations()['hu'], [
            'privacy_link_url' => '/privacy-policy/',

            // Nyelv beállítás
            'default_language' => 'hu',

            // Zaraz beállítások (Cloudflare)
            'use_zaraz' => false,
            'zaraz_purpose_name' => 'marketing',
            'gtm_container_id' => '',

            // Színek
            'primary_color' => '#111111',
            'primary_text_color' => '#ffffff',
            'secondary_color' => '#ffffff',
            'secondary_text_color' => '#000000',
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'backdrop_color' => 'rgba(0,0,0,0.4)',
            'border_color' => '#d0d0d0',
            'link_color' => '#0066cc',

            // Egyéb beállítások
            'ttl_days' => 180,
            'border_radius' => 12,
            'show_open_button' => true,
            'show_reject_button' => true,
            'popup_position' => 'center', // center, bottom-left, bottom-right

            // Region-alapú viselkedés
            'eea_only_banner' => false,   // Ha true: csak a régiólista országaiból látja a banner
            'custom_regions' => '',       // Vesszővel/sortöréssel elválasztott 2 betűs országkódok; üres = EEA alapértelmezett

            // Google Ads Conversion Modeling
            'use_google_ads' => false,    // Ha true: url_passthrough=true engedélyezve
            'wait_for_update_ms' => 1000, // GTM wait_for_update értéke (ms), minimum 500

            // Script blokkoló
            'blocker_enabled' => false,
            'blocker_rules' => '',        // Soronként "analytics|ads: minta"

            // Névtelen hozzájárulási statisztika
            'stats_enabled' => true,
        ]);
    }

    /**
     * Merged options. Precedence: defaults < language texts < saved values,
     * so texts edited by the admin are never overwritten by the built-in translations.
     */
    public static function get()
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $saved = get_option(CMV2_OPTION_KEY, []);
        $saved = is_array($saved) ? $saved : [];
        $defaults = self::defaults();
        $translations = self::translations();

        $lang = isset($saved['default_language']) ? $saved['default_language'] : $defaults['default_language'];
        $lang_texts = isset($translations[$lang]) ? $translations[$lang] : [];

        self::$cache = array_merge($defaults, $lang_texts, $saved);
        return self::$cache;
    }

    /**
     * Drop the request-level cache (called whenever the option is written)
     */
    public static function flush()
    {
        self::$cache = null;
    }
}

add_action('add_option_' . CMV2_OPTION_KEY, ['CMV2_Options', 'flush']);
add_action('update_option_' . CMV2_OPTION_KEY, ['CMV2_Options', 'flush']);
add_action('delete_option_' . CMV2_OPTION_KEY, ['CMV2_Options', 'flush']);
