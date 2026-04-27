<?php

/**
 * Plugin Name: CM V2 – Minimal WP MU Plugin (Consent Mode v2)
 * Description: Minimal saját fejlesztésű consent banner WordPress-hez (MU plugin). GCM v2 default/update jelek, cookie alapú tárolás, preferencia-módosítás. Telepítés: place into wp-content/mu-plugins/
 * Plugin URI: https://github.com/laposlaszlo/wp-consent-mode-v2-mu-plugin
 * Author: Lapos László
 * Author URI: https://laposlaszlo.com
 * Version: 2.8.7
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CMV2_VERSION', '2.8.7');
define('CMV2_CONSENT_VERSION', '2025-10-09');
define('CMV2_PLUGIN_DIR', dirname(__FILE__));
define('CMV2_PLUGIN_URL', plugins_url('', __FILE__));
define('CMV2_PLUGIN_FILE', __FILE__);

// Option keys
define('CMV2_OPTION_KEY', 'cmv2_settings');
define('CMV2_NONCE_ACTION', 'cmv2_settings_action');
define('CMV2_NONCE_NAME', 'cmv2_settings_nonce');
define('CMV2_MENU_SLUG', 'cmv2-settings');
define('CMV2_LS_KEY', 'cmv2_state');

// Load classes
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-settings.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-frontend.php';

// Többnyelvű szövegek
function cmv2_get_translations()
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

// Alapértelmezett beállítások
function cmv2_get_default_options()
{
    return [
        // Szövegek (magyar alapértelmezett)
        'title' => 'Sütibeállítások',
        'description' => 'Az oldal sütiket és hasonló technológiákat használ a működéshez, statisztikához és hirdetésekhez. A nem feltétlenül szükséges elemeket csak az engedélyed után aktiváljuk.',
        'privacy_link_text' => 'Adatkezelési tájékoztató',
        'privacy_link_url' => '/privacy-policy/',
        'necessary_label' => 'Szükséges',
        'analytics_label' => 'Analitika (GA4)',
        'ads_label' => 'Hirdetés & Marketing (Google Ads, Meta)',
        'accept_all_text' => 'Elfogadok mindent',
        'reject_all_text' => 'Csak szükséges',
        'save_text' => 'Mentés',
        'open_button_text' => 'Sütibeállítások',
        'customize_text' => 'Testreszabás',

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
        'popup_position' => 'center', // center, bottom-left, bottom-right

        // Region-alapú viselkedés (Phase 2)
        'eea_only_banner' => false,   // Ha true: csak EEA látogatóknak jelenik meg a banner
        'custom_regions' => '',       // Vesszővel/sortöréssel elválasztott 2 betűs országkódok; üres = EEA alapértelmezett

        // Google Ads Conversion Modeling (Phase 3)
        'use_google_ads' => false,    // Ha true: url_passthrough=true engedélyezve
        'wait_for_update_ms' => 1000, // GTM wait_for_update értéke (ms), minimum 500
    ];
}

// Beállítások lekérése
function cmv2_get_options()
{
    $defaults = cmv2_get_default_options();
    $saved = get_option(CMV2_OPTION_KEY, []);
    $options = wp_parse_args($saved, $defaults);
    
    // Nyelvspecifikus szövegek betöltése
    $translations = cmv2_get_translations();
    $lang = isset($options['default_language']) ? $options['default_language'] : 'hu';
    
    if (isset($translations[$lang])) {
        foreach ($translations[$lang] as $key => $value) {
            $options[$key] = $value;
        }
    }
    
    return $options;
}

// Initialize classes
CMV2_Settings::init();
CMV2_Frontend::init();

// Initialize Update Checker
add_action('plugins_loaded', 'cmv2_init_update_checker');

/**
 * Initialize Plugin Update Checker
 * Checks for updates from GitHub repository
 */
function cmv2_init_update_checker()
{
    // Prefer the plugin-update-checker loader to avoid Composer autoload conflicts.
    $puc_loader = CMV2_PLUGIN_DIR . '/vendor/yahnis-elsts/plugin-update-checker/load-v5p6.php';
    if (file_exists($puc_loader)) {
        require_once $puc_loader;
    } else {
        // Fallback to Composer autoload if the loader is missing.
        $autoload_file = CMV2_PLUGIN_DIR . '/vendor/autoload.php';
        if (!file_exists($autoload_file)) {
            return; // Dependencies not installed
        }
        require_once $autoload_file;
    }

    // Check if Plugin Update Checker class exists
    if (
        !class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory') &&
        !class_exists('YahnisElsts\PluginUpdateChecker\v5p6\PucFactory')
    ) {
        return;
    }

    // Initialize update checker for GitHub
    $updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/laposlaszlo/wp-consent-mode-v2-mu-plugin',
        CMV2_PLUGIN_FILE,
        'wp-consent-mode-v2-mu-plugin'
    );

    // Configure to use release assets or zipball
    $updateChecker->getVcsApi()->enableReleaseAssets();

    // Set the branch to check for updates
    $updateChecker->setBranch('main');

    // Optional: If using a private repository, set authentication token
    // Add to wp-config.php: define('CMV2_GITHUB_TOKEN', 'your_token');
    if (defined('CMV2_GITHUB_TOKEN') && CMV2_GITHUB_TOKEN) {
        $updateChecker->setAuthentication(CMV2_GITHUB_TOKEN);
    }
}

/**
 * Fix GTMKIT remove_from_cart ecommerce value bug.
 *
 * Older GTMKIT versions divide item price by 100 when computing ecommerce.value
 * for remove_from_cart (e.g. 22 500 HUF → 225). The push happens entirely in
 * GTMKIT's JS, so the correction must also be client-side.
 *
 * Strategy: after window.load (when GTM container is guaranteed to have loaded
 * and replaced dataLayer.push), we wrap push once more. Any remove_from_cart
 * event whose value is exactly price×qty/100 gets corrected to price×qty before
 * GTM processes it.
 *
 * Also adds currency to events that are missing it so Meta pixel value is correct.
 */
add_action('wp_footer', 'cmv2_fix_gtmkit_remove_from_cart_value', 1);
function cmv2_fix_gtmkit_remove_from_cart_value()
{
    if (!function_exists('WC')) {
        return;
    }
    ?>
    <script>
    /* CMV2: GTMKIT remove_from_cart value fix */
    window.addEventListener('load', function () {
        var dl = window.dataLayer;
        if (!dl || typeof dl.push !== 'function') return;

        var _gtmPush = dl.push;

        dl.push = function () {
            var args = Array.prototype.slice.call(arguments);

            for (var i = 0; i < args.length; i++) {
                var entry = args[i];

                if (
                    entry &&
                    entry.event === 'remove_from_cart' &&
                    entry.ecommerce &&
                    typeof entry.ecommerce.value === 'number' &&
                    Array.isArray(entry.ecommerce.items) &&
                    entry.ecommerce.items.length > 0
                ) {
                    var price = parseFloat(entry.ecommerce.items[0].price) || 0;
                    var qty   = parseInt(entry.ecommerce.items[0].quantity, 10) || 1;
                    var correct = price * qty;

                    /* Detect ÷100 bug: current value × 100 ≈ expected value */
                    if (correct > 0 && Math.round(entry.ecommerce.value * 100) === Math.round(correct)) {
                        entry.ecommerce.value = correct;
                    }

                    /* Ensure currency is present (some old GTMKIT versions omit it) */
                    if (!entry.ecommerce.currency && window.gtmkit_data && window.gtmkit_data.wc) {
                        entry.ecommerce.currency = window.gtmkit_data.wc.currency;
                    }
                }
            }

            return _gtmPush.apply(dl, args);
        };
    });
    </script>
    <?php
}
