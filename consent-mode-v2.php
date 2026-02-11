<?php

/**
 * Plugin Name: CM V2 – Minimal WP MU Plugin (Consent Mode v2)
 * Description: Minimal saját fejlesztésű consent banner WordPress-hez (MU plugin). GCM v2 default/update jelek, localStorage tárolás, preferencia-módosítás. Telepítés: place into wp-content/mu-plugins/
 * Plugin URI: https://github.com/laposlaszlo/wp-consent-mode-v2-mu-plugin
 * Author: Lapos László
 * Author URI: https://laposlaszlo.com
 * Version: 2.4.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CMV2_VERSION', '2.4.0');
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

// Alapértelmezett beállítások
function cmv2_get_default_options()
{
    return [
        // Szövegek
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
    ];
}

// Beállítások lekérése
function cmv2_get_options()
{
    $defaults = cmv2_get_default_options();
    $saved = get_option(CMV2_OPTION_KEY, []);
    return wp_parse_args($saved, $defaults);
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
    // Check if Composer autoload exists
    $autoload_file = CMV2_PLUGIN_DIR . '/vendor/autoload.php';
    if (!file_exists($autoload_file)) {
        return; // Composer dependencies not installed
    }

    require_once $autoload_file;

    // Check if Plugin Update Checker class exists
    if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
        return;
    }

    // Initialize update checker
    $updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/laposlaszlo/wp-consent-mode-v2-mu-plugin',
        CMV2_PLUGIN_FILE,
        'wp-consent-mode-v2-mu-plugin'
    );

    // Set the branch to check for updates (default is 'main')
    $updateChecker->setBranch('main');

    // Optional: Get the branch from the settings
    // Uncomment if you want to allow updates from different branches
    // $updateChecker->setBranch('develop');

    // Optional: If using a private repository, set authentication token
    // $updateChecker->setAuthentication('your-github-token');
}
