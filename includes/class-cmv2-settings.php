<?php
/**
 * CMV2 Settings Class
 * Handles admin settings page and configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMV2_Settings
{
    /**
     * Initialize settings
     */
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'handle_requests']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_filter('plugin_action_links_' . plugin_basename(CMV2_PLUGIN_DIR . '/consent-mode-v2.php'), [__CLASS__, 'add_settings_link']);
        add_action('admin_notices', [__CLASS__, 'admin_notice']);
    }

    /**
     * Add admin menu
     */
    public static function add_menu()
    {
        add_options_page(
            'Consent Mode V2 Settings',
            'Consent Mode V2',
            'manage_options',
            CMV2_MENU_SLUG,
            [__CLASS__, 'render_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_assets($hook)
    {
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

        // Pass color presets and translations to JavaScript
        wp_localize_script('cmv2-admin-js', 'CMV2_ADMIN', [
            'presets' => self::get_color_presets(),
            'translations' => cmv2_get_translations(),
            'i18n' => [
                'saved' => 'Beállítások mentve.',
                'savedGtm' => 'Mentve, de az érvénytelen GTM Container ID törölve lett.',
                'error' => 'A mentés nem sikerült. Próbáld újra.',
                'dirty' => 'Mentetlen változások',
                'clean' => 'Nincs mentetlen változás',
                'saving' => 'Mentés…',
                'confirmImport' => 'Biztosan felülírod a jelenlegi beállításokat?',
            ],
        ]);
    }

    /**
     * Add settings link to plugins page
     */
    public static function add_settings_link($links)
    {
        $settings_link = '<a href="' . admin_url('options-general.php?page=' . CMV2_MENU_SLUG) . '">Beállítások</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Admin notice for first-time setup
     */
    public static function admin_notice()
    {
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
    }

    /**
     * Get color presets
     */
    public static function get_color_presets()
    {
        return [
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
        ];
    }

    /**
     * Sanitize option value
     */
    public static function sanitize_option($key, $value)
    {
        if ($key === 'ttl_days' || $key === 'border_radius' || $key === 'wait_for_update_ms') {
            $int_value = intval($value);
            if ($key === 'ttl_days') {
                return max(1, min(365, $int_value));
            }
            if ($key === 'wait_for_update_ms') {
                return max(500, min(2000, $int_value));
            }
            return max(0, min(50, $int_value));
        } elseif ($key === 'show_open_button' || $key === 'show_reject_button' || $key === 'use_zaraz' || $key === 'eea_only_banner' || $key === 'use_google_ads' || $key === 'blocker_enabled' || $key === 'stats_enabled') {
            return (bool)$value;
        } elseif ($key === 'default_language') {
            return in_array($value, ['hu', 'en', 'fr', 'no']) ? $value : 'hu';
        } elseif ($key === 'zaraz_purpose_name') {
            // Ne használjunk sanitize_key()-t, mert az lowercase-re alakítja!
            // A Zaraz Purpose ID case-sensitive, használjunk sanitize_text_field()-et
            return sanitize_text_field($value);
        } elseif ($key === 'gtm_container_id') {
            $value = sanitize_text_field($value);
            if ($value === '') {
                return '';
            }
            return preg_match('/^GTM-[A-Z0-9]+$/i', $value) ? strtoupper($value) : '';
        } elseif ($key === 'popup_position') {
            return in_array($value, ['center', 'bottom-left', 'bottom-right']) ? $value : 'center';
        } elseif ($key === 'privacy_link_url') {
            return esc_url_raw($value);
        } elseif (strpos($key, 'color') !== false) {
            // Validate color formats: hex, rgb, rgba
            $value = sanitize_text_field($value);
            if (preg_match('/^(#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})|rgba?\\([^)]+\\))$/', $value)) {
                return $value;
            }
            return '';
        } elseif ($key === 'custom_regions') {
            // Only allow uppercase letters, commas, spaces and newlines (country codes)
            $cleaned = preg_replace('/[^A-Za-z,\s]/', '', sanitize_textarea_field($value));
            return strtoupper(trim($cleaned));
        } elseif ($key === 'description' || $key === 'blocker_rules') {
            return sanitize_textarea_field($value);
        } else {
            return sanitize_text_field($value);
        }
    }

    /**
     * Handle settings save
     */
    private static function handle_save()
    {
        if (!isset($_POST['cmv2_save_settings']) || !check_admin_referer(CMV2_NONCE_ACTION, CMV2_NONCE_NAME)) {
            return null;
        }

        $options = cmv2_get_default_options();
        $saved = [];
        $gtm_invalid = false;

        foreach ($options as $key => $default) {
            if (isset($_POST['cmv2_' . $key])) {
                $value = wp_unslash($_POST['cmv2_' . $key]);
                $sanitized = self::sanitize_option($key, $value);
                
                if ($key === 'gtm_container_id') {
                    if ($value === '') {
                        $saved[$key] = '';
                    } elseif ($sanitized === '') {
                        $gtm_invalid = true;
                        $saved[$key] = '';
                    } else {
                        $saved[$key] = $sanitized;
                    }
                    continue;
                }

                if ($sanitized !== '') {
                    $saved[$key] = $sanitized;
                }
            }
        }

        $saved['show_open_button'] = isset($_POST['cmv2_show_open_button']) ? true : false;
        $saved['show_reject_button'] = isset($_POST['cmv2_show_reject_button']) ? true : false;
        $saved['use_zaraz'] = isset($_POST['cmv2_use_zaraz']) ? true : false;
        $saved['eea_only_banner'] = isset($_POST['cmv2_eea_only_banner']) ? true : false;
        $saved['use_google_ads'] = isset($_POST['cmv2_use_google_ads']) ? true : false;
        $saved['blocker_enabled'] = isset($_POST['cmv2_blocker_enabled']) ? true : false;
        $saved['stats_enabled'] = isset($_POST['cmv2_stats_enabled']) ? true : false;

        update_option(CMV2_OPTION_KEY, $saved);
        return $gtm_invalid ? 'success_with_gtm_warning' : 'success';
    }

    /**
     * Handle statistics reset
     */
    private static function handle_stats_reset()
    {
        if (!isset($_POST['cmv2_reset_stats']) || !check_admin_referer(CMV2_NONCE_ACTION, CMV2_NONCE_NAME)) {
            return null;
        }

        CMV2_Stats::reset();
        return 'stats_reset';
    }

    /**
     * Handle settings export
     */
    private static function handle_export()
    {
        if (!isset($_POST['cmv2_export_settings']) || !check_admin_referer(CMV2_NONCE_ACTION, CMV2_NONCE_NAME)) {
            return;
        }

        $settings = get_option(CMV2_OPTION_KEY, []);
        $json = json_encode($settings, JSON_PRETTY_PRINT);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="cmv2-settings-' . date('Y-m-d') . '.json"');
        echo $json;
        exit;
    }

    /**
     * Handle settings import
     */
    private static function handle_import()
    {
        if (!isset($_POST['cmv2_import_settings']) || !check_admin_referer(CMV2_NONCE_ACTION, CMV2_NONCE_NAME)) {
            return null;
        }

        if (!isset($_FILES['cmv2_import_file']) || $_FILES['cmv2_import_file']['error'] !== UPLOAD_ERR_OK) {
            return 'upload_error';
        }

        // Validate file size (max 1MB)
        if ($_FILES['cmv2_import_file']['size'] > 1048576) {
            return 'file_too_large';
        }

        // Validate MIME type
        $file_type = wp_check_filetype($_FILES['cmv2_import_file']['name']);
        if ($file_type['ext'] !== 'json') {
            return 'invalid_type';
        }

        // Verify the file is a genuine PHP upload (not a path traversal attempt)
        if (!is_uploaded_file($_FILES['cmv2_import_file']['tmp_name'])) {
            return 'upload_error';
        }

        $json = file_get_contents($_FILES['cmv2_import_file']['tmp_name']);
        $imported = json_decode($json, true);

        if (!$imported || !is_array($imported) || json_last_error() !== JSON_ERROR_NONE) {
            return 'invalid_json';
        }

        // Validate and sanitize imported settings
        $defaults = cmv2_get_default_options();
        $validated = [];
        foreach ($defaults as $key => $default) {
            if (isset($imported[$key])) {
                $validated[$key] = self::sanitize_option($key, $imported[$key]);
            }
        }
        
        update_option(CMV2_OPTION_KEY, $validated);
        return 'import_success';
    }

    /**
     * Process save / export / import before any output is sent, then redirect
     * back to the page (Post/Redirect/Get) with a notice code.
     */
    public static function handle_requests()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== CMV2_MENU_SLUG || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        self::handle_export(); // Dies if export is triggered

        $result = self::handle_save();
        if ($result === null) {
            $result = self::handle_import();
        }
        if ($result === null) {
            $result = self::handle_stats_reset();
        }
        if ($result === null) {
            return;
        }

        $url = add_query_arg('cmv2_notice', $result, admin_url('options-general.php?page=' . CMV2_MENU_SLUG));
        wp_safe_redirect($result === 'stats_reset' ? $url . '#stats' : $url);
        exit;
    }

    /**
     * Build the notice markup for the result of the previous request
     */
    private static function get_notice_html()
    {
        $notices = [
            'success' => ['success', 'Beállítások sikeresen mentve!'],
            'success_with_gtm_warning' => ['success', 'Beállítások mentve, de az érvénytelen GTM Container ID törölve lett.'],
            'import_success' => ['success', 'Beállítások sikeresen importálva!'],
            'file_too_large' => ['error', 'A fájl túl nagy! Maximum 1MB.'],
            'invalid_type' => ['error', 'Csak JSON fájl engedélyezett!'],
            'invalid_json' => ['error', 'Hibás JSON formátum!'],
            'upload_error' => ['error', 'Fájl feltöltési hiba!'],
            'stats_reset' => ['success', 'A statisztika törölve lett.'],
        ];

        $code = isset($_GET['cmv2_notice']) ? sanitize_key(wp_unslash($_GET['cmv2_notice'])) : '';
        if (!isset($notices[$code])) {
            return '';
        }

        return sprintf(
            '<div class="notice notice-%s is-dismissible" data-cmv2-notice="%s"><p>%s</p></div>',
            esc_attr($notices[$code][0]),
            esc_attr($code),
            esc_html($notices[$code][1])
        );
    }

    /**
     * Render settings page
     */
    public static function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        CMV2_Admin_Page::render(cmv2_get_options(), self::get_notice_html());
    }
}
