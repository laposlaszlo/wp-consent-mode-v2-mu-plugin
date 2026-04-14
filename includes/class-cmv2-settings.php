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
            'translations' => cmv2_get_translations()
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
        } elseif ($key === 'show_open_button' || $key === 'use_zaraz' || $key === 'eea_only_banner' || $key === 'use_google_ads') {
            return (bool)$value;
        } elseif ($key === 'default_language') {
            return in_array($value, ['hu', 'en', 'fr']) ? $value : 'hu';
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
            if (preg_match('/^(#[0-9a-fA-F]{3,6}|rgba?\\([^)]+\\))$/', $value)) {
                return $value;
            }
            return '';
        } elseif ($key === 'custom_regions') {
            // Only allow uppercase letters, commas, spaces and newlines (country codes)
            $cleaned = preg_replace('/[^A-Za-z,\s]/', '', sanitize_textarea_field($value));
            return strtoupper(trim($cleaned));
        } elseif ($key === 'description') {
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
        $saved['use_zaraz'] = isset($_POST['cmv2_use_zaraz']) ? true : false;
        $saved['eea_only_banner'] = isset($_POST['cmv2_eea_only_banner']) ? true : false;
        $saved['use_google_ads'] = isset($_POST['cmv2_use_google_ads']) ? true : false;

        update_option(CMV2_OPTION_KEY, $saved);
        return $gtm_invalid ? 'success_with_gtm_warning' : 'success';
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
     * Render settings page
     */
    public static function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submissions
        $save_result = self::handle_save();
        self::handle_export(); // Dies if export is triggered
        $import_result = self::handle_import();

        // Display notices
        if ($save_result === 'success' || $save_result === 'success_with_gtm_warning') {
            echo '<div class="notice notice-success is-dismissible"><p>Beállítások sikeresen mentve!</p></div>';
        }
        if ($save_result === 'success_with_gtm_warning') {
            echo '<div class="notice notice-error is-dismissible"><p>Érvénytelen GTM Container ID. A mező törölve lett.</p></div>';
        }

        if ($import_result === 'import_success') {
            echo '<div class="notice notice-success is-dismissible"><p>Beállítások sikeresen importálva!</p></div>';
        } elseif ($import_result === 'file_too_large') {
            echo '<div class="notice notice-error is-dismissible"><p>A fájl túl nagy! Maximum 1MB.</p></div>';
        } elseif ($import_result === 'invalid_type') {
            echo '<div class="notice notice-error is-dismissible"><p>Csak JSON fájl engedélyezett!</p></div>';
        } elseif ($import_result === 'invalid_json') {
            echo '<div class="notice notice-error is-dismissible"><p>Hibás JSON formátum!</p></div>';
        } elseif ($import_result === 'upload_error') {
            echo '<div class="notice notice-error is-dismissible"><p>Fájl feltöltési hiba!</p></div>';
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
                    </nav>

                    <?php self::render_texts_tab($options); ?>
                    <?php self::render_colors_tab($options); ?>
                    <?php self::render_advanced_tab($options); ?>
                </div>

                <p class="submit">
                    <input type="submit" name="cmv2_save_settings" class="button button-primary button-large" value="Beállítások mentése" />
                    <a href="<?php echo home_url(); ?>" class="button button-secondary" target="_blank">Előnézet</a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render texts tab
     */
    private static function render_texts_tab($options)
    {
        $translations = cmv2_get_translations();
        ?>
        <div id="tab-texts" class="cmv2-tab-content active">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cmv2_default_language">Nyelv</label></th>
                    <td>
                        <select id="cmv2_default_language" name="cmv2_default_language" class="regular-text">
                            <option value="hu" <?php selected($options['default_language'], 'hu'); ?>>🇭🇺 Magyar</option>
                            <option value="en" <?php selected($options['default_language'], 'en'); ?>>🇬🇧 English</option>
                            <option value="fr" <?php selected($options['default_language'], 'fr'); ?>>🇫🇷 Français</option>
                        </select>
                        <p class="description">Válaszd ki a banner nyelvét. A kiválasztott nyelvhez tartozó szövegek automatikusan betöltődnek.</p>
                    </td>
                </tr>
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
        <?php
    }

    /**
     * Render colors tab
     */
    private static function render_colors_tab($options)
    {
        $presets = self::get_color_presets();
        ?>
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
        <?php
    }

    /**
     * Render advanced tab
     */
    private static function render_advanced_tab($options)
    {
        ?>
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
                <tr>
                    <th scope="row"><label for="cmv2_popup_position">Popup pozíció</label></th>
                    <td>
                        <select id="cmv2_popup_position" name="cmv2_popup_position">
                            <option value="center" <?php selected($options['popup_position'], 'center'); ?>>Középen</option>
                            <option value="bottom-left" <?php selected($options['popup_position'], 'bottom-left'); ?>>Lent balra</option>
                            <option value="bottom-right" <?php selected($options['popup_position'], 'bottom-right'); ?>>Lent jobbra</option>
                        </select>
                        <p class="description">Válaszd ki, hol jelenjen meg a cookie banner</p>
                    </td>
                </tr>
            </table>

            <h3>Region-alapú Viselkedés</h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cmv2_eea_only_banner">Banner csak EEA látogatóknak</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cmv2_eea_only_banner" name="cmv2_eea_only_banner" value="1" <?php checked($options['eea_only_banner'], true); ?> />
                            Banner és consent JS csak EEA/GDPR országokból érkező látogatóknak
                        </label>
                        <p class="description">
                            <strong>⚠️ Feltétel:</strong> Cloudflare (<code>CF-IPCountry</code> header) vagy más proxy (<code>X-Country-Code</code>) szükséges az országdetekcióhoz.<br>
                            Ha az ország nem azonosítható, a banner mindig megjelenik (biztonságos fallback).
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cmv2_custom_regions">Egyedi régió lista</label></th>
                    <td>
                        <textarea id="cmv2_custom_regions" name="cmv2_custom_regions" rows="3" class="large-text" placeholder="AT, BE, DE, HU, ..."><?php echo esc_textarea($options['custom_regions']); ?></textarea>
                        <p class="description">
                            Vesszővel vagy sortöréssel elválasztott 2 betűs ISO-3166-1 országkódok (pl. <code>AT, BE, DE, HU</code>).<br>
                            <strong>Hagyd üresen</strong> az alapértelmezett EEA/GDPR lista használatához (AT, BE, BG, HR, CY, CZ, DK, EE, FI, FR, DE, GR, HU, IE, IT, LV, LT, LU, MT, NL, PL, PT, RO, SK, SI, ES, SE, GB, IS, LI, NO, CH).
                        </p>
                    </td>
                </tr>
            </table>

            <h3>Cloudflare Zaraz Integráció</h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cmv2_use_zaraz">Zaraz használata</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cmv2_use_zaraz" name="cmv2_use_zaraz" value="1" <?php checked($options['use_zaraz'], true); ?> />
                            Zaraz consent kezelés engedélyezése (Cloudflare)
                        </label>
                        <p class="description">Ha Cloudflare Zaraz-t használsz (FB Pixel, stb.), kapcsold be ezt az opciót.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cmv2_zaraz_purpose_name">Zaraz Purpose ID</label></th>
                    <td>
                        <input type="text" id="cmv2_zaraz_purpose_name" name="cmv2_zaraz_purpose_name" value="<?php echo esc_attr($options['zaraz_purpose_name']); ?>" class="regular-text" placeholder="marketing" />
                        <p class="description">
                            <strong>⚠️ FONTOS:</strong> Add meg a Purpose <strong>ID-ját</strong> (nem a nevét!) <strong>PONTOS kis/nagybetűkkel!</strong><br>
                            📍 <strong>Hol találod meg:</strong> Cloudflare Dashboard → Zaraz → Settings → Consent Management → Purposes<br>
                            Kattints a Purpose-re és <strong>másold ki</strong> az <code>ID</code> mezőt (pl. "marketing", "kujO", "analytics").<br>
                            🔴 Az ID <strong>case-sensitive</strong>: "kujo" ≠ "kujO" ≠ "KUJO"<br>
                            Ha hibát kapsz a konzolban, ellenőrizd hogy ez az ID létezik-e és pontosan egyezik-e!
                        </p>
                    </td>
                </tr>
            </table>

            <h3>Google Tag Manager</h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cmv2_gtm_container_id">GTM Container ID</label></th>
                    <td>
                        <input type="text" id="cmv2_gtm_container_id" name="cmv2_gtm_container_id" value="<?php echo esc_attr($options['gtm_container_id']); ?>" class="regular-text" placeholder="GTM-XXXXXXX" />
                        <p class="description">Ha megadod, a GTM konténer automatikusan betöltődik a default consent után.</p>
                    </td>
                </tr>
                <?php if (!empty($options['gtm_container_id'])): ?>
                <tr>
                    <th scope="row">GTM duplikáció figyelmeztetés</th>
                    <td>
                        <div class="notice notice-warning inline" style="margin:0; padding: 8px 12px;">
                            <p style="margin:0;">
                                <strong>⚠️ Ellenőrizd a GTM duplikációt!</strong><br>
                                A plugin betölti a GTM konténert (<code><?php echo esc_html($options['gtm_container_id']); ?></code>).<br>
                                Ha a GTM <strong>kétszer töltődik be</strong>, ellenőrizd:<br>
                                &bull; Theme <code>header.php</code> – nincs-e benne GTM snippet<br>
                                &bull; Egyéb plugin (pl. MonsterInsights, Site Kit, WooCommerce) – nem injektál-e GTM-et<br>
                                &bull; Checkout oldalon nincs-e külön GTM snippet<br>
                                <em>A plugin beépített <code>_cmv2_gtm_loaded_*</code> guard védi a saját duplikációtól. Ha kettes betöltést látsz, a forrás külső.</em>
                            </p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <h3>Google Ads Conversion Modeling</h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cmv2_use_google_ads">Google Ads aktív</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cmv2_use_google_ads" name="cmv2_use_google_ads" value="1" <?php checked($options['use_google_ads'], true); ?> />
                            <code>url_passthrough</code> engedélyezése (Google Ads gclid/gbraid URL-követés)
                        </label>
                        <p class="description">
                            Csak akkor kapcsold be, ha Google Ads konverziókövetést használsz.<br>
                            <code>ads_data_redaction</code> mindig aktív — denied állapotban is küld modellezhető jeleket a Google-nek.<br>
                            <strong>GTM Conversion Linker tag:</strong> Ajánlott hozzáadni a GTM-ben, hogy a <code>gclid</code> előző kattintásból átvitődjön (All Pages trigger + Conversion Linker tag).
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cmv2_wait_for_update_ms">wait_for_update (ms)</label></th>
                    <td>
                        <input type="number" id="cmv2_wait_for_update_ms" name="cmv2_wait_for_update_ms" value="<?php echo esc_attr(max(500, intval($options['wait_for_update_ms']))); ?>" min="500" max="2000" />
                        <p class="description">Mennyi ideig várjon a GTM a consent frissítésére az indulás előtt. Minimum 500ms, az ajánlott alapérték 1000ms.</p>
                    </td>
                </tr>
            </table>

            <h3>Consent Mode Státusz</h3>
            <div class="cmv2-status-box">
                <p><strong>Google Consent Mode V2:</strong> ✅ Aktív</p>
                <p><strong>Default consent:</strong> Beállítva (cookie alapján, különben denied)</p>
                <p><strong>Update consent:</strong> Beállítva (felhasználói választás alapján)</p>
                <p><strong>GTM események:</strong> cm_default, cm_update</p>
                <p><strong>GTM konténer:</strong> <?php echo $options['gtm_container_id'] ? '✅ ' . esc_html($options['gtm_container_id']) : '❌ Nincs megadva'; ?></p>
                <p><strong>Cloudflare Zaraz:</strong> <?php echo $options['use_zaraz'] ? '✅ Engedélyezve (' . esc_html($options['zaraz_purpose_name']) . ')' : '❌ Letiltva'; ?></p>
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
        <?php
    }
}
