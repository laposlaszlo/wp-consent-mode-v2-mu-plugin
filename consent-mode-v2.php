<?php

/**
 * Plugin Name: CM V2 – Minimal Consent Mode v2
 * Description: Minimal saját fejlesztésű consent banner WordPress-hez. GCM v2 default/update jelek, cookie alapú tárolás, preferencia-módosítás. Frissítés GitHub Release-ekből.
 * Plugin URI: https://github.com/laposlaszlo/wp-consent-mode-v2-mu-plugin
 * Update URI: https://github.com/laposlaszlo/wp-consent-mode-v2-mu-plugin
 * Author: Lapos László
 * Author URI: https://laposlaszlo.com
 * Version: 2.9.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

// A régi mu-plugins példány már betöltődött: ne töltsük be kétszer (osztály-redeklarálás fatal lenne).
if (defined('CMV2_PLUGIN_FILE')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-warning"><p><strong>Consent Mode V2:</strong> '
            . 'A régi <code>mu-plugins</code> példány még aktív. Töröld a <code>wp-content/mu-plugins/</code> alól, '
            . 'hogy a normál plugin (automatikus frissítéssel) átvegye a helyét.</p></div>';
    });
    return;
}

define('CMV2_VERSION', '2.9.1');
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
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-options.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-admin-page.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-settings.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-frontend.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-integrations.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-blocker.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-stats.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-updater.php';

// Backward-compatible helpers (the logic lives in CMV2_Options)
if (!function_exists('cmv2_get_translations')) {
    function cmv2_get_translations()
    {
        return CMV2_Options::translations();
    }
}

if (!function_exists('cmv2_get_default_options')) {
    function cmv2_get_default_options()
    {
        return CMV2_Options::defaults();
    }
}

if (!function_exists('cmv2_get_options')) {
    function cmv2_get_options()
    {
        return CMV2_Options::get();
    }
}

// Initialize classes
CMV2_Settings::init();
CMV2_Frontend::init();
CMV2_Integrations::init();
CMV2_Blocker::init();
CMV2_Stats::init();

register_activation_hook(CMV2_PLUGIN_FILE, ['CMV2_Stats', 'install']);
register_deactivation_hook(CMV2_PLUGIN_FILE, ['CMV2_Stats', 'deactivate']);

// GitHub Releases alapú frissítés
CMV2_Updater::init();
