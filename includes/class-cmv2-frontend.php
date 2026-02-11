<?php
/**
 * CMV2 Frontend Class
 * Handles frontend consent banner rendering and assets
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMV2_Frontend
{
    /**
     * Initialize frontend
     */
    public static function init()
    {
        add_action('wp_head', [__CLASS__, 'render_default_consent'], 0);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [__CLASS__, 'render_banner'], 99);
    }

    /**
     * Render default consent in head
     */
    public static function render_default_consent()
    {
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
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueue_assets()
    {
        // CSS betöltése
        wp_enqueue_style(
            'cmv2-banner-css',
            CMV2_PLUGIN_URL . '/assets/css/consent-banner.css',
            [],
            CMV2_VERSION
        );

        // Dinamikus inline CSS a testreszabott színekhez
        $opts = cmv2_get_options();
        $custom_css = self::generate_custom_css($opts);
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
            'ttl_days' => intval($opts['ttl_days']),
            'popup_position' => $opts['popup_position'],
            'translations' => cmv2_get_translations(),
            'default_language' => $opts['default_language'],
            'available_languages' => $opts['available_languages'],
            'show_language_selector' => $opts['show_language_selector']
        ]);
    }

    /**
     * Generate custom CSS
     */
    private static function generate_custom_css($opts)
    {
        $css = "
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
            $css .= "
        .cmv2-open {
            border: 1px solid {$opts['border_color']};
            background: {$opts['background_color']};
            color: {$opts['text_color']};
        }";
        }

        return $css;
    }

    /**
     * Render banner markup
     */
    public static function render_banner()
    {
        $opts = cmv2_get_options();
        
        // Determine position class
        $position_class = '';
        if ($opts['popup_position'] === 'bottom-left') {
            $position_class = ' cmv2-position-bottom-left';
        } elseif ($opts['popup_position'] === 'bottom-right') {
            $position_class = ' cmv2-position-bottom-right';
        }
        ?>
        <div id="cmv2-modal" class="cmv2-hidden<?php echo esc_attr($position_class); ?>" aria-hidden="true" role="dialog" aria-labelledby="cmv2-title" aria-modal="true">
            <div class="cmv2-backdrop"></div>
            <div class="cmv2-window" role="document">
                <?php if ($opts['show_language_selector']): ?>
                    <div class="cmv2-language-selector">
                        <?php foreach ($opts['available_languages'] as $lang): ?>
                            <button class="cmv2-lang-btn" data-lang="<?php echo esc_attr($lang); ?>" aria-label="<?php echo esc_attr(strtoupper($lang)); ?>">
                                <?php echo esc_html(strtoupper($lang)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <h2 id="cmv2-title" data-i18n="title"><?php echo esc_html($opts['title']); ?></h2>
                <p data-i18n="description"><?php echo esc_html($opts['description']); ?></p>
                <p><a href="<?php echo esc_url($opts['privacy_link_url']); ?>" target="_blank" rel="noopener" data-i18n="privacy_link_text"><?php echo esc_html($opts['privacy_link_text']); ?></a></p>

                <!-- Egyszerű nézet (alapértelmezett) -->
                <div id="cmv2-simple-view" class="cmv2-view">
                    <div class="cmv2-actions">
                        <button id="cmv2-accept-all-simple" class="cmv2-btn cmv2-primary cmv2-btn-large" data-i18n="accept_all_text"><?php echo esc_html($opts['accept_all_text']); ?></button>
                        <button id="cmv2-customize" class="cmv2-btn cmv2-secondary cmv2-btn-large" data-i18n="customize_text">Testreszabás</button>
                    </div>
                </div>

                <!-- Részletes nézet (kapcsolókkal) -->
                <div id="cmv2-detailed-view" class="cmv2-view cmv2-hidden">
                    <div class="cmv2-groups">
                        <label class="cmv2-row cmv2-disabled">
                            <span data-i18n="necessary_label"><?php echo esc_html($opts['necessary_label']); ?></span>
                            <input type="checkbox" checked disabled aria-label="<?php echo esc_attr($opts['necessary_label']); ?>" />
                        </label>
                        <label class="cmv2-row">
                            <span data-i18n="analytics_label"><?php echo esc_html($opts['analytics_label']); ?></span>
                            <input id="cmv2-analytics" type="checkbox" aria-label="<?php echo esc_attr($opts['analytics_label']); ?>" />
                        </label>
                        <label class="cmv2-row">
                            <span data-i18n="ads_label"><?php echo esc_html($opts['ads_label']); ?></span>
                            <input id="cmv2-ads" type="checkbox" aria-label="<?php echo esc_attr($opts['ads_label']); ?>" />
                        </label>
                    </div>
 data-i18n="open_button_text"
                    <div class="cmv2-actions">
                        <button id="cmv2-accept-all-detailed" class="cmv2-btn cmv2-primary" data-i18n="accept_all_text"><?php echo esc_html($opts['accept_all_text']); ?></button>
                        <button id="cmv2-save" class="cmv2-btn cmv2-secondary" data-i18n="save_text"><?php echo esc_html($opts['save_text']); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($opts['show_open_button']): ?>
            <button id="cmv2-open" class="cmv2-open" aria-label="<?php echo esc_attr($opts['open_button_text']); ?>"><?php echo esc_html($opts['open_button_text']); ?></button>
        <?php endif; ?>
        <?php
    }
}
