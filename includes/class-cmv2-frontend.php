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
        // Only run on frontend
        if (is_admin()) {
            return;
        }
        
        // Use wp_head with the earliest priority to beat other tags/snippets.
        // Note: If a tag is hard-coded before wp_head() in header.php, it will still run first.
        add_action('wp_head', [__CLASS__, 'render_default_consent'], 0);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_body_open', [__CLASS__, 'render_gtm_noscript'], 0);
        add_action('wp_footer', [__CLASS__, 'render_banner'], 99);
    }
    
    /**
     * Determine whether the consent banner (and its assets) should be shown
     * to the current visitor. If eea_only_banner is enabled we check the
     * country header set by Cloudflare (HTTP_CF_IPCOUNTRY) or a generic
     * proxy (HTTP_X_COUNTRY_CODE). Unknown country → show banner (safe fallback).
     */
    public static function is_banner_needed($opts)
    {
        if (empty($opts['eea_only_banner'])) {
            return true;
        }

        $country = '';
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $country = strtoupper(sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])));
        } elseif (!empty($_SERVER['HTTP_X_COUNTRY_CODE'])) {
            $country = strtoupper(sanitize_text_field(wp_unslash($_SERVER['HTTP_X_COUNTRY_CODE'])));
        }

        // Empty or 'XX' (Cloudflare anonymous) → safe fallback: show banner
        if ($country === '' || $country === 'XX') {
            return true;
        }

        return in_array($country, CMV2_Options::regions($opts), true);
    }

    /**
     * Render default consent in head.
     *
     * The stored consent cookie is evaluated in the browser, not in PHP, so the
     * markup is identical for every visitor and stays safe behind page caches.
     */
    public static function render_default_consent()
    {
        $opts = cmv2_get_options();
        $gtm_container_id = isset($opts['gtm_container_id']) ? $opts['gtm_container_id'] : '';
        $config = [
            'cookie' => CMV2_LS_KEY,
            'version' => CMV2_CONSENT_VERSION,
            'ttl_days' => max(1, intval($opts['ttl_days'])),
            'wait_for_update' => max(500, intval($opts['wait_for_update_ms'])),
            'url_passthrough' => !empty($opts['use_google_ads']),
            'regions' => CMV2_Options::regions($opts),
        ];
        ?>
        <script>
            // dataLayer + gtag bootstrap (ártalmatlan, ha már létezik)
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            // Consent Mode v2 – DEFAULT:
            // - A default MINDIG 'denied' – ez az új látogatók biztonságos alapállapota.
            // - Ha van érvényes cookie, azonnal következik egy 'update' a tárolt értékekkel.
            // - wait_for_update: visszatérő látogatónál rövid (alapból 1000ms), újnál 30000ms,
            //   hogy a GTM ne tüzeljen a felhasználó döntése előtt.
            // - region: EU/EEA lista (GDPR-only default)
            (function (cfg) {
                try {
                    var stored = { analytics: 'denied', ads: 'denied' };
                    var hasPrior = false;

                    // A cookie-t a böngésző olvassa, így a HTML cache-elhető
                    try {
                        var m = document.cookie.match('(?:^|; )' + cfg.cookie + '=([^;]*)');
                        var state = m ? JSON.parse(decodeURIComponent(m[1])) : null;
                        var age = state ? Math.floor(Date.now() / 1000) - parseInt(state.ts, 10) : 0;
                        if (state && state.version === cfg.version && !isNaN(age) && age <= cfg.ttl_days * 86400) {
                            var c = state.choices || {};
                            stored.analytics = c.analytics ? 'granted' : 'denied';
                            stored.ads = c.ads ? 'granted' : 'denied';
                            hasPrior = stored.analytics !== 'denied' || stored.ads !== 'denied';
                        }
                    } catch (e) {}

                    // url_passthrough: csak akkor, ha Google Ads aktív (gclid/gbraid URL-paraméteres követés)
                    if (cfg.url_passthrough) {
                        gtag('set', 'url_passthrough', true);
                    }
                    // ads_data_redaction kikapcsolva: denied esetén is küld modellezhető jelet
                    gtag('set', 'ads_data_redaction', false);

                    gtag('consent', 'default', {
                        'ad_storage': 'denied',
                        'analytics_storage': 'denied',
                        'ad_user_data': 'denied',
                        'ad_personalization': 'denied',
                        'functionality_storage': 'granted',
                        'necessary_storage': 'granted',
                        'wait_for_update': hasPrior ? cfg.wait_for_update : 30000,
                        'region': cfg.regions
                    });

                    if (hasPrior) {
                        // Visszatérő látogató: a GTM betöltése előtt frissítjük a tárolt hozzájárulással
                        gtag('consent', 'update', {
                            'ad_storage': stored.ads,
                            'analytics_storage': stored.analytics,
                            'ad_user_data': stored.ads,
                            'ad_personalization': stored.ads
                        });
                    }

                    // Jelzés a GTM felé (debughoz / integrációhoz hasznos)
                    window.dataLayer.push({
                        'event': 'cm_default',
                        'cmv2_version': cfg.version,
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
                    console.warn('CMV2: consent default init failed', e);
                }
            })(<?php echo wp_json_encode($config); ?>);
        </script>
        <?php if (!empty($gtm_container_id)): ?>
            <script>
                // GTM de-duplication guard: prevents double injection if theme or another
                // plugin already loaded this container (or this snippet runs more than once).
                (function(w,d,s,l,i){
                    if (w['_cmv2_gtm_loaded_'+i]) { return; }
                    w['_cmv2_gtm_loaded_'+i] = 1;
                    w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','<?php echo esc_js($gtm_container_id); ?>');
            </script>
        <?php endif; ?>
        <?php
    }

    /**
     * Render GTM noscript (recommended fallback) after body open.
     */
    public static function render_gtm_noscript()
    {
        $opts = cmv2_get_options();
        $gtm_container_id = isset($opts['gtm_container_id']) ? $opts['gtm_container_id'] : '';
        if (empty($gtm_container_id)) {
            return;
        }
        ?>
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($gtm_container_id); ?>"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
        <?php
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueue_assets()
    {
        $opts = cmv2_get_options();

        // EEA-only: ne töltse be a bannert nem-EEA látogatóknak
        if (!self::is_banner_needed($opts)) {
            return;
        }

        // CSS betöltése
        wp_enqueue_style(
            'cmv2-banner-css',
            CMV2_PLUGIN_URL . '/assets/css/consent-banner.css',
            [],
            CMV2_VERSION
        );

        // Dinamikus inline CSS a testreszabott színekhez
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
            'cookie' => CMV2_LS_KEY,
            'ttl_days' => intval($opts['ttl_days']),
            'popup_position' => $opts['popup_position'],
            'use_zaraz' => (bool)$opts['use_zaraz'],
            'zaraz_purpose_name' => $opts['zaraz_purpose_name'],
            'gtm_container_id' => isset($opts['gtm_container_id']) ? $opts['gtm_container_id'] : '',
            'stats_url' => !empty($opts['stats_enabled']) ? rest_url('cmv2/v1/event') : '',
            'stats_token' => !empty($opts['stats_enabled']) ? CMV2_Stats::token() : '',
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

        // EEA-only: ne rendereld a bannert nem-EEA látogatóknak
        if (!self::is_banner_needed($opts)) {
            return;
        }

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
                <h2 id="cmv2-title"><?php echo esc_html($opts['title']); ?></h2>
                <p><?php echo esc_html($opts['description']); ?></p>
                <p><a href="<?php echo esc_url($opts['privacy_link_url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($opts['privacy_link_text']); ?></a></p>

                <!-- Egyszerű nézet (alapértelmezett) -->
                <div id="cmv2-simple-view" class="cmv2-view">
                    <div class="cmv2-actions">
                        <button id="cmv2-accept-all-simple" class="cmv2-btn cmv2-primary cmv2-btn-large"><?php echo esc_html($opts['accept_all_text']); ?></button>
                        <?php if (!empty($opts['show_reject_button'])): ?>
                        <button id="cmv2-reject-all" class="cmv2-btn cmv2-secondary cmv2-btn-large"><?php echo esc_html($opts['reject_all_text']); ?></button>
                        <?php endif; ?>
                        <button id="cmv2-customize" class="cmv2-btn cmv2-secondary cmv2-btn-large"><?php echo esc_html($opts['customize_text']); ?></button>
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
    }
}
