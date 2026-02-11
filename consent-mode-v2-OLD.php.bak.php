<?php

/**
 * Plugin Name: CM V2 – Minimal WP MU Plugin (Consent Mode v2)
 * Description: Minimal saját fejlesztésű consent banner WordPress-hez (MU plugin). GCM v2 default/update jelek, localStorage tárolás, preferencia-módosítás. Telepítés: place into wp-content/mu-plugins/
 * Author: Lapos László
 * Version: 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CMV2_VERSION', '2.3.0');
define('CMV2_CONSENT_VERSION', '2025-10-09');
define('CMV2_PLUGIN_DIR', dirname(__FILE__));
define('CMV2_PLUGIN_URL', plugins_url('', __FILE__));

// Option keys
define('CMV2_OPTION_KEY', 'cmv2_settings');
define('CMV2_NONCE_ACTION', 'cmv2_settings_action');
define('CMV2_NONCE_NAME', 'cmv2_settings_nonce');
define('CMV2_MENU_SLUG', 'cmv2-settings');
define('CMV2_LS_KEY', 'cmv2_state');
// Load classes
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-settings.php';
require_once CMV2_PLUGIN_DIR . '/includes/class-cmv2-frontend.php';
// Alapértelezett beállítások
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
    ];
}

// Beállítások lekérése
function cmv2_get_options()
{
    $defaults = cmv2_get_default_options();
    $saved = get_option(CMV2_OPTION_KEY, []);
    return wp_parse_args($saved, $defaults);
}

// Input sanitization helper
function cmv2_sanitize_option($key, $value)
{
    if ($key === 'ttl_days' || $key === 'border_radius') {
        $int_value = intval($value);
        if ($key === 'ttl_days') {
            return max(1, min(365, $int_value));
        }
        return max(0, min(50, $int_value));
    } elseif ($key === 'show_open_button') {
        return (bool)$value;
    } elseif ($key === 'privacy_link_url') {
        return esc_url_raw($value);
    } elseif (strpos($key, 'color') !== false) {
        // Validate color formats: hex, rgb, rgba
        $value = sanitize_text_field($value);
        if (preg_match('/^(#[0-9a-fA-F]{3,6}|rgba?\([^)]+\))$/', $value)) {
            return $value;
        }
        return '';
    } elseif ($key === 'description') {
        return sanitize_textarea_field($value);
    } else {
        return sanitize_text_field($value);
    }
}

// Initialize classes
CMV2_Settings::init();
CMV2_Frontend::init();
});

// Beállítások oldal
function cmv2_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Beállítások mentése
    if (isset($_POST['cmv2_save_settings']) && check_admin_referer(CMV2_NONCE_ACTION, CMV2_NONCE_NAME)) {
        $options = cmv2_get_default_options();
        $saved = [];

        foreach ($options as $key => $default) {
            if (isset($_POST['cmv2_' . $key])) {
                $value = wp_unslash($_POST['cmv2_' . $key]);
                $sanitized = cmv2_sanitize_option($key, $value);
                
                if ($sanitized !== '') {
                    $saved[$key] = $sanitized;
                }
            }
        }

        $saved['show_open_button'] = isset($_POST['cmv2_show_open_button']) ? true : false;

        update_option(CMV2_OPTION_KEY, $saved);
        echo '<div class="notice notice-success is-dismissible"><p>Beállítások sikeresen mentve!</p></div>';
    }

    // Beállítások exportálása
    if (isset($_POST['cmv2_export_settings']) && check_admin_referer(CMV2_NONCE_ACTION, CMV2_NONCE_NAME)) {
        $settings = get_option(CMV2_OPTION_KEY, []);
        $json = json_encode($settings, JSON_PRETTY_PRINT);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="cmv2-settings-' . date('Y-m-d') . '.json"');
        echo $json;
        exit;
    }

    // Beállítások importálása
    if (isset($_POST['cmv2_import_settings']) && check_admin_referer(CMV2_NONCE_ACTION, CMV2_NONCE_NAME)) {
        if (isset($_FILES['cmv2_import_file']) && $_FILES['cmv2_import_file']['error'] === UPLOAD_ERR_OK) {
            // Validate file size (max 1MB)
            if ($_FILES['cmv2_import_file']['size'] > 1048576) {
                echo '<div class="notice notice-error is-dismissible"><p>A fájl túl nagy! Maximum 1MB.</p></div>';
            } else {
                // Validate MIME type
                $file_type = wp_check_filetype($_FILES['cmv2_import_file']['name']);
                if ($file_type['ext'] !== 'json') {
                    echo '<div class="notice notice-error is-dismissible"><p>Csak JSON fájl engedélyezett!</p></div>';
                } else {
                    $json = file_get_contents($_FILES['cmv2_import_file']['tmp_name']);
                    $imported = json_decode($json, true);

                    if ($imported && is_array($imported) && json_last_error() === JSON_ERROR_NONE) {
                        // Validate and sanitize imported settings
                        $defaults = cmv2_get_default_options();
                        $validated = [];
                        foreach ($defaults as $key => $default) {
                            if (isset($imported[$key])) {
                                $validated[$key] = cmv2_sanitize_option($key, $imported[$key]);
                            }
                        }
                        update_option(CMV2_OPTION_KEY, $validated);
                        echo '<div class="notice notice-success is-dismissible"><p>Beállítások sikeresen importálva!</p></div>';
                    } else {
                        echo '<div class="notice notice-error is-dismissible"><p>Hibás JSON formátum!</p></div>';
                    }
                }
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Fájl feltöltési hiba!</p></div>';
        }
    }

    $options = cmv2_get_options();
    ?>
    <div class="wrap">
        <h1>Consent Mode V2 Beállítások</h1>
        <p>Testreszabhatod a cookie banner megjelenését és szövegeit.</p>

        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field(CMV2_NONCE_ACTION, CMV2_NONCE_NAME); ?>

            <div class="cmv2-admin-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#tab-texts" class="nav-tab nav-tab-active">Szövegek</a>
                    <a href="#tab-colors" class="nav-tab">Színek</a>
                    <a href="#tab-advanced" class="nav-tab">Haladó</a>
                </nav>                <!-- SZÖVEGEK TAB -->
                <div id="tab-texts" class="cmv2-tab-content active">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="cmv2_title">Banner címsor</label></th>
                            <td><input type="text" id="cmv2_title" name="cmv2_title" value="<?php echo esc_attr($options['title']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_description">Leírás</label></th>
                            <td><textarea id="cmv2_description" name="cmv2_description" rows="3" class="large-text"><?php echo esc_textarea($options['description']); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_privacy_link_text">Adatvédelmi link szövege</label></th>
                            <td><input type="text" id="cmv2_privacy_link_text" name="cmv2_privacy_link_text" value="<?php echo esc_attr($options['privacy_link_text']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_privacy_link_url">Adatvédelmi link URL</label></th>
                            <td><input type="text" id="cmv2_privacy_link_url" name="cmv2_privacy_link_url" value="<?php echo esc_attr($options['privacy_link_url']); ?>" class="regular-text" placeholder="/privacy-policy/" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_necessary_label">Szükséges sütik címke</label></th>
                            <td><input type="text" id="cmv2_necessary_label" name="cmv2_necessary_label" value="<?php echo esc_attr($options['necessary_label']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_analytics_label">Analitika címke</label></th>
                            <td><input type="text" id="cmv2_analytics_label" name="cmv2_analytics_label" value="<?php echo esc_attr($options['analytics_label']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_ads_label">Hirdetés címke</label></th>
                            <td><input type="text" id="cmv2_ads_label" name="cmv2_ads_label" value="<?php echo esc_attr($options['ads_label']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_accept_all_text">"Elfogad mindent" gomb szövege</label></th>
                            <td><input type="text" id="cmv2_accept_all_text" name="cmv2_accept_all_text" value="<?php echo esc_attr($options['accept_all_text']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_reject_all_text">"Elutasít" gomb szövege</label></th>
                            <td><input type="text" id="cmv2_reject_all_text" name="cmv2_reject_all_text" value="<?php echo esc_attr($options['reject_all_text']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_save_text">"Mentés" gomb szövege</label></th>
                            <td><input type="text" id="cmv2_save_text" name="cmv2_save_text" value="<?php echo esc_attr($options['save_text']); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_open_button_text">Megnyitó gomb szövege</label></th>
                            <td><input type="text" id="cmv2_open_button_text" name="cmv2_open_button_text" value="<?php echo esc_attr($options['open_button_text']); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <!-- SZÍNEK TAB -->
                <div id="tab-colors" class="cmv2-tab-content">
                    <div class="cmv2-color-presets">
                        <h3>Gyors Színsémák</h3>
                        <div class="preset-buttons">
                            <button type="button" class="button preset-btn" data-preset="default">
                                <span class="preset-preview" style="background: linear-gradient(135deg, #111 0%, #fff 100%);"></span>
                                Alapértelmezett
                            </button>
                            <button type="button" class="button preset-btn" data-preset="blue">
                                <span class="preset-preview" style="background: linear-gradient(135deg, #0066cc 0%, #e6f2ff 100%);"></span>
                                Modern Kék
                            </button>
                            <button type="button" class="button preset-btn" data-preset="green">
                                <span class="preset-preview" style="background: linear-gradient(135deg, #2ecc71 0%, #f0fff4 100%);"></span>
                                Eco Zöld
                            </button>
                            <button type="button" class="button preset-btn" data-preset="purple">
                                <span class="preset-preview" style="background: linear-gradient(135deg, #9b59b6 0%, #f4ecf7 100%);"></span>
                                Elegáns Lila
                            </button>
                            <button type="button" class="button preset-btn" data-preset="dark">
                                <span class="preset-preview" style="background: linear-gradient(135deg, #1a1a1a 0%, #4a9eff 100%);"></span>
                                Dark Mode
                            </button>
                            <button type="button" class="button preset-btn" data-preset="orange">
                                <span class="preset-preview" style="background: linear-gradient(135deg, #ff6b35 0%, #fff5f0 100%);"></span>
                                Meleg Narancs
                            </button>
                        </div>
                    </div>

                    <hr style="margin: 30px 0;">

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="cmv2_primary_color">Elsődleges szín (gombok)</label></th>
                            <td>
                                <input type="text" id="cmv2_primary_color" name="cmv2_primary_color" value="<?php echo esc_attr($options['primary_color']); ?>" class="cmv2-color-picker" />
                                <p class="description">Az "Elfogadok mindent" gomb háttérszíne</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_primary_text_color">Elsődleges szövegszín</label></th>
                            <td>
                                <input type="text" id="cmv2_primary_text_color" name="cmv2_primary_text_color" value="<?php echo esc_attr($options['primary_text_color']); ?>" class="cmv2-color-picker" />
                                <p class="description">Az elsődleges gomb szövegszíne</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_secondary_color">Másodlagos szín</label></th>
                            <td>
                                <input type="text" id="cmv2_secondary_color" name="cmv2_secondary_color" value="<?php echo esc_attr($options['secondary_color']); ?>" class="cmv2-color-picker" />
                                <p class="description">A "Mentés" gomb háttérszíne</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_secondary_text_color">Másodlagos szövegszín</label></th>
                            <td>
                                <input type="text" id="cmv2_secondary_text_color" name="cmv2_secondary_text_color" value="<?php echo esc_attr($options['secondary_text_color']); ?>" class="cmv2-color-picker" />
                                <p class="description">A másodlagos gombok szövegszíne</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_background_color">Háttérszín</label></th>
                            <td>
                                <input type="text" id="cmv2_background_color" name="cmv2_background_color" value="<?php echo esc_attr($options['background_color']); ?>" class="cmv2-color-picker" />
                                <p class="description">A modal ablak háttérszíne</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_text_color">Szövegszín</label></th>
                            <td>
                                <input type="text" id="cmv2_text_color" name="cmv2_text_color" value="<?php echo esc_attr($options['text_color']); ?>" class="cmv2-color-picker" />
                                <p class="description">A fő szöveg színe</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_backdrop_color">Háttér árnyékolás</label></th>
                            <td>
                                <input type="text" id="cmv2_backdrop_color" name="cmv2_backdrop_color" value="<?php echo esc_attr($options['backdrop_color']); ?>" class="regular-text" />
                                <p class="description">RGBA érték (pl: rgba(0,0,0,0.4))</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_border_color">Szegélyszín</label></th>
                            <td>
                                <input type="text" id="cmv2_border_color" name="cmv2_border_color" value="<?php echo esc_attr($options['border_color']); ?>" class="cmv2-color-picker" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_link_color">Link szín</label></th>
                            <td>
                                <input type="text" id="cmv2_link_color" name="cmv2_link_color" value="<?php echo esc_attr($options['link_color']); ?>" class="cmv2-color-picker" />
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- HALADÓ TAB -->
                <div id="tab-advanced" class="cmv2-tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="cmv2_ttl_days">Süti élettartam (napok)</label></th>
                            <td>
                                <input type="number" id="cmv2_ttl_days" name="cmv2_ttl_days" value="<?php echo esc_attr($options['ttl_days']); ?>" min="1" max="365" />
                                <p class="description">Ennyi nap után kérjünk újra beleegyezést (alapértelmezett: 180)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_border_radius">Sarkok lekerekítése (px)</label></th>
                            <td>
                                <input type="number" id="cmv2_border_radius" name="cmv2_border_radius" value="<?php echo esc_attr($options['border_radius']); ?>" min="0" max="50" />
                                <p class="description">A modal ablak sarkainak lekerekítése pixelben</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cmv2_show_open_button">Megnyitó gomb megjelenítése</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="cmv2_show_open_button" name="cmv2_show_open_button" value="1" <?php checked($options['show_open_button'], true); ?> />
                                    Bal alsó sarokban rögzített gomb megjelenítése
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3>Google Consent Mode V2 Státusz</h3>
                    <div class="cmv2-status-box">
                        <p><strong>Default consent</strong> beállítva (minden denied)</p>
                        <p><strong>Update consent</strong> beállítva (felhasználói választás alapján)</p>
                        <p><strong>GTM események:</strong> cm_default, cm_update</p>
                        <p><strong>Megfelelőség:</strong> GDPR, Google Consent Mode V2</p>
                        <p><strong>Verzió:</strong> <?php echo CMV2_VERSION; ?> (<?php echo CMV2_CONSENT_VERSION; ?>)</p>
                    </div>

                    <h3>Export / Import Beállítások</h3>
                    <div class="cmv2-status-box" style="background: #fff9e6; border-color: #f39c12;">
                        <p><strong>Export:</strong> Mentsd le a jelenlegi beállításaidat JSON fájlba.</p>
                        <button type="submit" name="cmv2_export_settings" class="button">Beállítások exportálása</button>

                        <hr style="margin: 20px 0;">

                        <p><strong>Import:</strong> Töltsd be korábban mentett beállításaidat.</p>
                        <input type="file" name="cmv2_import_file" accept=".json" id="cmv2_import_file" style="margin-bottom: 10px;">
                        <button type="submit" name="cmv2_import_settings" class="button" onclick="return confirm('Biztosan felülírod a jelenlegi beállításokat?');">Beállítások importálása</button>
                    </div>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="cmv2_save_settings" class="button button-primary button-large" value="Beállítások mentése" />
                <a href="<?php echo home_url(); ?>" class="button button-secondary" target="_blank">Előnézet</a>
            </p>
        </form>
    </div>
    <?php
}

// Color picker script és admin assets betöltése az admin oldalon
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_' . CMV2_MENU_SLUG) {
        return;
    }

    // WordPress Color Picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // Admin CSS
    wp_enqueue_style(
        'cmv2-admin-css',
        CMV2_PLUGIN_URL . '/assets/css/admin.css',
        [],
        CMV2_VERSION
    );

    // Admin JS
    wp_enqueue_script(
        'cmv2-admin-js',
        CMV2_PLUGIN_URL . '/assets/js/admin.js',
        ['jquery', 'wp-color-picker'],
        CMV2_VERSION,
        true
    );

    // Pass color presets to JavaScript
    wp_localize_script('cmv2-admin-js', 'CMV2_ADMIN', [
        'presets' => [
            'default' => [
                'primary_color' => '#111111',
                'primary_text_color' => '#ffffff',
                'secondary_color' => '#ffffff',
                'secondary_text_color' => '#000000',
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'border_color' => '#d0d0d0',
                'link_color' => '#0066cc'
            ],
            'blue' => [
                'primary_color' => '#0066cc',
                'primary_text_color' => '#ffffff',
                'secondary_color' => '#e6f2ff',
                'secondary_text_color' => '#0066cc',
                'background_color' => '#ffffff',
                'text_color' => '#2c3e50',
                'border_color' => '#b3d9ff',
                'link_color' => '#0052a3'
            ],
            'green' => [
                'primary_color' => '#2ecc71',
                'primary_text_color' => '#ffffff',
                'secondary_color' => '#f0fff4',
                'secondary_text_color' => '#27ae60',
                'background_color' => '#ffffff',
                'text_color' => '#2c3e50',
                'border_color' => '#a8e6cf',
                'link_color' => '#27ae60'
            ],
            'purple' => [
                'primary_color' => '#9b59b6',
                'primary_text_color' => '#ffffff',
                'secondary_color' => '#f4ecf7',
                'secondary_text_color' => '#8e44ad',
                'background_color' => '#ffffff',
                'text_color' => '#34495e',
                'border_color' => '#d7bde2',
                'link_color' => '#8e44ad'
            ],
            'dark' => [
                'primary_color' => '#ffffff',
                'primary_text_color' => '#000000',
                'secondary_color' => '#333333',
                'secondary_text_color' => '#ffffff',
                'background_color' => '#1a1a1a',
                'text_color' => '#ffffff',
                'border_color' => '#444444',
                'link_color' => '#4a9eff'
            ],
            'orange' => [
                'primary_color' => '#ff6b35',
                'primary_text_color' => '#ffffff',
                'secondary_color' => '#fff5f0',
                'secondary_text_color' => '#d35400',
                'background_color' => '#ffffff',
                'text_color' => '#2c3e50',
                'border_color' => '#ffccbc',
                'link_color' => '#e74c3c'
            ]
        ]
    ]);
});

// Settings link a plugin listában
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=' . CMV2_MENU_SLUG) . '">Beállítások</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Admin notice ha még nincsenek testreszabott beállítások
add_action('admin_notices', function () {
    $opts = get_option(CMV2_OPTION_KEY);
    if (empty($opts) && current_user_can('manage_options')) {
        $screen = get_current_screen();
        if ($screen && $screen->id !== 'settings_page_' . CMV2_MENU_SLUG) {
    ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>Consent Mode V2:</strong>
                    A plugin aktív! <a href="<?php echo admin_url('options-general.php?page=' . CMV2_MENU_SLUG); ?>">Kattints ide</a>
                    a szövegek és színek testreszabásához.
                </p>
            </div>
    <?php
        }
    }
});

// 1) DEFAULT CONSENT a <head> legelején – minden más script előtt
add_action('wp_head', function () {
    ?>
    <script>
        // dataLayer + gtag bootstrap (ártalmatlan, ha már létezik)
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        // Consent Mode v2 – DEFAULT:
        // - Szükséges és funkcionális/analitika engedélyezve alapértelmezettként
        // - Hirdetés/marketing tiltva alapértelmezettként
        // - wait_for_update: 500 (GTM vár a frissítésre)
        // - region: EU/EEA list (GDPR-only default)
        // - url_passthrough és ads_data_redaction ajánlott beállítások
        try {
            gtag('set', 'url_passthrough', true);
            gtag('set', 'ads_data_redaction', true);

            gtag('consent', 'default', {
                'ad_storage': 'denied',
                'analytics_storage': 'denied', // functional/analytics enabled by default
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'functionality_storage': 'granted',
                'necessary_storage': 'granted',
                'wait_for_update': 500,
                'region': ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'IS', 'LI', 'NO', 'CH']
            });

            // Jelzés a GTM felé (debughoz / integrációhoz hasznos)
            window.dataLayer.push({
                'event': 'cm_default',
                'cmv2_version': '<?php echo CMV2_CONSENT_VERSION; ?>',
                'consent_default': {
                    ad_storage: 'denied',
                    analytics_storage: 'denied',
                    ad_user_data: 'denied',
                    ad_personalization: 'denied',
                    functionality_storage: 'granted',
                    necessary_storage: 'granted'
                }
            });
        } catch (e) {
            // defensive: if gtag() or dataLayer unavailable, still continue without throwing
            console.warn('CMV2: consent default init failed', e);
        }
    </script>
<?php
}, 0);

// 2) Frontend assets betöltése
add_action('wp_enqueue_scripts', function () {
    // CSS betöltése
    wp_enqueue_style(
        'cmv2-banner-css',
        CMV2_PLUGIN_URL . '/assets/css/consent-banner.css',
        [],
        CMV2_VERSION
    );

    // Dinamikus inline CSS a testreszabott színekhez
    $opts = cmv2_get_options();
    $custom_css = "
        #cmv2-modal .cmv2-backdrop { background: {$opts['backdrop_color']}; }
        #cmv2-modal .cmv2-window {
            background: {$opts['background_color']};
            color: {$opts['text_color']};
            border-radius: {$opts['border_radius']}px;
        }
        #cmv2-modal h2 { color: {$opts['text_color']}; }
        #cmv2-modal p { color: {$opts['text_color']}; }
        #cmv2-modal a { color: {$opts['link_color']}; }
        .cmv2-row { border-bottom: 1px solid {$opts['border_color']}; }
        .cmv2-btn { border: 1px solid {$opts['border_color']}; }
        .cmv2-primary {
            background: {$opts['primary_color']};
            color: {$opts['primary_text_color']};
            border-color: {$opts['primary_color']};
        }
        .cmv2-secondary {
            background: {$opts['secondary_color']};
            color: {$opts['secondary_text_color']};
        }";

    if ($opts['show_open_button']) {
        $custom_css .= "
        .cmv2-open {
            border: 1px solid {$opts['border_color']};
            background: {$opts['background_color']};
            color: {$opts['text_color']};
        }";
    }

    wp_add_inline_style('cmv2-banner-css', $custom_css);

    // JavaScript betöltése
    wp_enqueue_script(
        'cmv2-banner-js',
        CMV2_PLUGIN_URL . '/assets/js/consent-banner.js',
        [],
        CMV2_VERSION,
        true
    );

    // Konfigurációs adatok átadása JavaScriptnek
    wp_localize_script('cmv2-banner-js', 'CMV2_CONFIG', [
        'version' => CMV2_CONSENT_VERSION,
        'ttl_days' => intval($opts['ttl_days'])
    ]);
});

// 3) Banner markup a footerben (DOM végén)
add_action('wp_footer', function () {
    $opts = cmv2_get_options();
?>
    <div id="cmv2-modal" class="cmv2-hidden" aria-hidden="true" role="dialog" aria-labelledby="cmv2-title" aria-modal="true">
        <div class="cmv2-backdrop"></div>
        <div class="cmv2-window" role="document">
            <h2 id="cmv2-title"><?php echo esc_html($opts['title']); ?></h2>
            <p><?php echo esc_html($opts['description']); ?></p>
            <p><a href="<?php echo esc_url($opts['privacy_link_url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($opts['privacy_link_text']); ?></a></p>

            <!-- Egyszerű nézet (alapértelmezett) -->
            <div id="cmv2-simple-view" class="cmv2-view">
                <div class="cmv2-actions">
                    <button id="cmv2-accept-all-simple" class="cmv2-btn cmv2-primary cmv2-btn-large"><?php echo esc_html($opts['accept_all_text']); ?></button>
                    <button id="cmv2-customize" class="cmv2-btn cmv2-secondary cmv2-btn-large">Testreszabás</button>
                </div>
            </div>

            <!-- Részletes nézet (kapcsolókkal) -->
            <div id="cmv2-detailed-view" class="cmv2-view cmv2-hidden">
                <div class="cmv2-groups">
                    <label class="cmv2-row cmv2-disabled">
                        <span><?php echo esc_html($opts['necessary_label']); ?></span>
                        <input type="checkbox" checked disabled aria-label="<?php echo esc_attr($opts['necessary_label']); ?>" />
                    </label>
                    <label class="cmv2-row">
                        <span><?php echo esc_html($opts['analytics_label']); ?></span>
                        <input id="cmv2-analytics" type="checkbox" aria-label="<?php echo esc_attr($opts['analytics_label']); ?>" />
                    </label>
                    <label class="cmv2-row">
                        <span><?php echo esc_html($opts['ads_label']); ?></span>
                        <input id="cmv2-ads" type="checkbox" aria-label="<?php echo esc_attr($opts['ads_label']); ?>" />
                    </label>
                </div>

                <div class="cmv2-actions">
                    <button id="cmv2-accept-all-detailed" class="cmv2-btn cmv2-primary"><?php echo esc_html($opts['accept_all_text']); ?></button>
                    <button id="cmv2-save" class="cmv2-btn cmv2-secondary"><?php echo esc_html($opts['save_text']); ?></button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($opts['show_open_button']): ?>
        <button id="cmv2-open" class="cmv2-open" aria-label="<?php echo esc_attr($opts['open_button_text']); ?>"><?php echo esc_html($opts['open_button_text']); ?></button>
    <?php endif; ?>
<?php
}, 99);
