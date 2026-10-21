<?php
/**
 * CMV2 Admin Page Class
 * Renders the settings screen (sidebar navigation, cards, live preview)
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMV2_Admin_Page
{
    /**
     * Sidebar sections: id => [label, dashicon]
     */
    private static function sections()
    {
        return [
            'general' => ['Általános', 'admin-generic'],
            'texts' => ['Szövegek', 'editor-paragraph'],
            'appearance' => ['Megjelenés', 'art'],
            'integrations' => ['Integrációk', 'networking'],
            'blocker' => ['Szkriptblokkoló', 'lock'],
            'regions' => ['Régiók', 'location-alt'],
            'stats' => ['Statisztika', 'chart-bar'],
            'status' => ['Állapot és mentés', 'shield-alt'],
        ];
    }

    /**
     * Render the whole page
     */
    public static function render($options, $notice_html)
    {
        ?>
        <div class="wrap cmv2-app" id="cmv2-app">
            <h1 class="screen-reader-text">Consent Mode V2 beállítások</h1>
            <hr class="wp-header-end">

            <header class="cmv2-header">
                <div class="cmv2-brand">
                    <span class="cmv2-brand-mark dashicons dashicons-shield-alt" aria-hidden="true"></span>
                    <div>
                        <strong>Consent Mode V2</strong>
                        <span>Cookie banner és Google Consent Mode beállítások</span>
                    </div>
                </div>
                <div class="cmv2-header-meta">
                    <span class="cmv2-chip"><?php echo esc_html('v' . CMV2_VERSION); ?></span>
                    <span class="cmv2-chip cmv2-chip-ok">GCM v2 aktív</span>
                </div>
            </header>

            <?php echo $notice_html; // Already escaped in CMV2_Settings::render_notice() ?>

            <div class="cmv2-layout">
                <nav class="cmv2-nav" aria-label="Beállítások szekciói">
                    <?php foreach (self::sections() as $id => $section): ?>
                        <a href="#<?php echo esc_attr($id); ?>" class="cmv2-nav-item" data-section="<?php echo esc_attr($id); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($section[1]); ?>" aria-hidden="true"></span>
                            <?php echo esc_html($section[0]); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <main class="cmv2-main">
                    <form method="post" action="" id="cmv2-form">
                        <?php wp_nonce_field(CMV2_NONCE_ACTION, CMV2_NONCE_NAME); ?>

                        <?php self::section_general($options); ?>
                        <?php self::section_texts($options); ?>
                        <?php self::section_appearance($options); ?>
                        <?php self::section_integrations($options); ?>
                        <?php self::section_blocker($options); ?>
                        <?php self::section_regions($options); ?>

                        <div class="cmv2-savebar" role="region" aria-label="Mentés">
                            <span class="cmv2-savebar-state" id="cmv2-save-state" aria-live="polite">Nincs mentetlen változás</span>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="button" target="_blank" rel="noopener">Oldal megnyitása</a>
                            <button type="submit" name="cmv2_save_settings" value="1" class="button button-primary" id="cmv2-save-btn">Beállítások mentése</button>
                        </div>
                    </form>

                    <?php self::section_stats($options); ?>
                    <?php self::section_status($options); ?>
                </main>

                <?php self::preview($options); ?>
            </div>

            <div class="cmv2-toast" id="cmv2-toast" role="status" aria-live="polite" hidden></div>
        </div>
        <?php
    }

    // ------------------------------------------------------------------
    // Field helpers
    // ------------------------------------------------------------------

    private static function help($html)
    {
        if ($html === '') {
            return;
        }
        echo '<p class="cmv2-help">' . wp_kses($html, ['code' => [], 'strong' => [], 'em' => [], 'br' => []]) . '</p>';
    }

    private static function field_text($key, $label, $options, $desc = '', $extra = [])
    {
        $type = isset($extra['type']) ? $extra['type'] : 'text';
        $class = isset($extra['class']) ? $extra['class'] : '';
        $placeholder = isset($extra['placeholder']) ? $extra['placeholder'] : '';
        ?>
        <div class="cmv2-field">
            <label for="cmv2_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
            <input type="<?php echo esc_attr($type); ?>" id="cmv2_<?php echo esc_attr($key); ?>" name="cmv2_<?php echo esc_attr($key); ?>"
                   value="<?php echo esc_attr($options[$key]); ?>" class="<?php echo esc_attr($class); ?>"
                   <?php echo $placeholder !== '' ? 'placeholder="' . esc_attr($placeholder) . '"' : ''; ?>
                   <?php foreach (['min', 'max', 'step'] as $attr) {
                       if (isset($extra[$attr])) {
                           echo ' ' . $attr . '="' . esc_attr($extra[$attr]) . '"';
                       }
                   } ?> />
            <?php self::help($desc); ?>
        </div>
        <?php
    }

    private static function field_textarea($key, $label, $options, $desc = '', $placeholder = '')
    {
        ?>
        <div class="cmv2-field">
            <label for="cmv2_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
            <textarea id="cmv2_<?php echo esc_attr($key); ?>" name="cmv2_<?php echo esc_attr($key); ?>" rows="3"
                      <?php echo $placeholder !== '' ? 'placeholder="' . esc_attr($placeholder) . '"' : ''; ?>><?php echo esc_textarea($options[$key]); ?></textarea>
            <?php self::help($desc); ?>
        </div>
        <?php
    }

    private static function field_toggle($key, $label, $options, $desc = '', $depends = '')
    {
        ?>
        <label class="cmv2-switch-row" <?php echo $depends !== '' ? 'data-depends="' . esc_attr($depends) . '"' : ''; ?> for="cmv2_<?php echo esc_attr($key); ?>">
            <span class="cmv2-switch">
                <input type="checkbox" id="cmv2_<?php echo esc_attr($key); ?>" name="cmv2_<?php echo esc_attr($key); ?>" value="1" <?php checked(!empty($options[$key])); ?> />
                <span class="cmv2-slider" aria-hidden="true"></span>
            </span>
            <span class="cmv2-switch-text">
                <strong><?php echo esc_html($label); ?></strong>
                <?php if ($desc !== ''): ?><small><?php echo wp_kses($desc, ['code' => [], 'strong' => [], 'em' => [], 'br' => []]); ?></small><?php endif; ?>
            </span>
        </label>
        <?php
    }

    private static function card_open($title, $desc = '')
    {
        echo '<div class="cmv2-card"><div class="cmv2-card-head"><h2>' . esc_html($title) . '</h2>';
        if ($desc !== '') {
            echo '<p>' . wp_kses($desc, ['code' => [], 'strong' => []]) . '</p>';
        }
        echo '</div><div class="cmv2-card-body">';
    }

    private static function card_close()
    {
        echo '</div></div>';
    }

    private static function section_open($id, $title)
    {
        echo '<section class="cmv2-section" id="cmv2-sec-' . esc_attr($id) . '" data-section="' . esc_attr($id) . '" aria-labelledby="cmv2-sec-' . esc_attr($id) . '-title">';
        echo '<h2 class="cmv2-section-title" id="cmv2-sec-' . esc_attr($id) . '-title">' . esc_html($title) . '</h2>';
    }

    private static function section_close()
    {
        echo '</section>';
    }

    // ------------------------------------------------------------------
    // Sections
    // ------------------------------------------------------------------

    private static function section_general($options)
    {
        self::section_open('general', 'Általános');

        self::card_open('Nyelv és élettartam');
        ?>
        <div class="cmv2-field">
            <label for="cmv2_default_language">Banner nyelve</label>
            <select id="cmv2_default_language" name="cmv2_default_language">
                <option value="hu" <?php selected($options['default_language'], 'hu'); ?>>🇭🇺 Magyar</option>
                <option value="en" <?php selected($options['default_language'], 'en'); ?>>🇬🇧 English</option>
                <option value="fr" <?php selected($options['default_language'], 'fr'); ?>>🇫🇷 Français</option>
                <option value="no" <?php selected($options['default_language'], 'no'); ?>>🇳🇴 Norsk</option>
            </select>
            <?php self::help('Váltáskor a Szövegek szekció mezői az adott nyelv alapszövegeivel töltődnek fel (mentésig átírhatók).'); ?>
        </div>
        <?php
        self::field_text('ttl_days', 'Hozzájárulás érvényessége (nap)', $options, 'Ennyi nap után kérünk újra hozzájárulást. Alapértelmezett: 180.', ['type' => 'number', 'min' => 1, 'max' => 365, 'class' => 'small-text']);
        self::card_close();

        self::card_open('Elhelyezés és gombok');
        ?>
        <fieldset class="cmv2-field">
            <legend>Banner pozíciója</legend>
            <div class="cmv2-segmented" role="radiogroup">
                <?php
                $positions = ['center' => 'Középen', 'bottom-left' => 'Lent balra', 'bottom-right' => 'Lent jobbra'];
                foreach ($positions as $value => $label): ?>
                    <label class="cmv2-seg">
                        <input type="radio" name="cmv2_popup_position" value="<?php echo esc_attr($value); ?>" <?php checked($options['popup_position'], $value); ?> />
                        <span class="cmv2-seg-tile">
                            <span class="cmv2-seg-glyph cmv2-glyph-<?php echo esc_attr($value); ?>" aria-hidden="true"><i></i></span>
                            <?php echo esc_html($label); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php
        self::field_toggle('show_reject_button', '„Csak szükséges” gomb', $options, 'Ha kikapcsolod, az első nézetben csak az „Elfogadok mindent” és a „Testreszabás” látszik.');
        self::field_toggle('show_open_button', 'Állandó megnyitó gomb', $options, 'Bal alsó sarokban rögzített gomb, amellyel a látogató bármikor módosíthatja a döntését.');
        self::card_close();

        self::card_open('Névtelen statisztika');
        self::field_toggle('stats_enabled', 'Hozzájárulási statisztika gyűjtése', $options, 'Naponta összesített számlálók a banner megjelenéséről és a döntésekről. Nem tárol IP-címet, sütit vagy azonosítót. Az eredmény a Statisztika szekcióban látható. Ez nem jogi bizonyíték a hozzájárulásról.');
        self::card_close();

        self::section_close();
    }

    private static function section_texts($options)
    {
        self::section_open('texts', 'Szövegek');

        self::card_open('Banner szövegei');
        self::field_text('title', 'Címsor', $options);
        self::field_textarea('description', 'Leírás', $options);
        self::field_text('privacy_link_text', 'Adatvédelmi link szövege', $options);
        self::field_text('privacy_link_url', 'Adatvédelmi link URL', $options, '', ['placeholder' => '/privacy-policy/']);
        self::card_close();

        self::card_open('Kategóriák és gombok');
        echo '<div class="cmv2-grid-2">';
        self::field_text('necessary_label', 'Szükséges sütik', $options);
        self::field_text('analytics_label', 'Analitika', $options);
        self::field_text('ads_label', 'Hirdetés', $options);
        self::field_text('open_button_text', 'Megnyitó gomb', $options);
        self::field_text('accept_all_text', '„Elfogadok mindent” gomb', $options);
        self::field_text('reject_all_text', '„Csak szükséges” gomb', $options);
        self::field_text('customize_text', '„Testreszabás” gomb', $options);
        self::field_text('save_text', '„Mentés” gomb', $options);
        echo '</div>';
        self::card_close();

        self::section_close();
    }

    private static function section_appearance($options)
    {
        $preset_names = [
            'default' => 'Alapértelmezett',
            'blue' => 'Modern kék',
            'green' => 'Eco zöld',
            'purple' => 'Elegáns lila',
            'dark' => 'Sötét',
            'orange' => 'Meleg narancs',
        ];
        $presets = CMV2_Settings::get_color_presets();

        self::section_open('appearance', 'Megjelenés');

        self::card_open('Színséma', 'Válassz egy sémát kiindulásnak, majd finomhangold lent.');
        echo '<div class="cmv2-presets">';
        foreach ($preset_names as $key => $name) {
            $p = $presets[$key];
            printf(
                '<button type="button" class="cmv2-preset" data-preset="%s"><span class="cmv2-preset-swatch" style="background:%s"><i style="background:%s"></i><i style="background:%s"></i></span><span>%s</span></button>',
                esc_attr($key),
                esc_attr($p['background_color']),
                esc_attr($p['primary_color']),
                esc_attr($p['secondary_color']),
                esc_html($name)
            );
        }
        echo '</div>';
        self::card_close();

        self::card_open('Színek');
        echo '<div class="cmv2-grid-2">';
        $colors = [
            'primary_color' => ['Elsődleges szín', 'Az „Elfogadok mindent” gomb háttere'],
            'primary_text_color' => ['Elsődleges szövegszín', 'Az elsődleges gomb szövege'],
            'secondary_color' => ['Másodlagos szín', 'A másodlagos gombok háttere'],
            'secondary_text_color' => ['Másodlagos szövegszín', 'A másodlagos gombok szövege'],
            'background_color' => ['Háttérszín', 'A banner ablak háttere'],
            'text_color' => ['Szövegszín', 'A fő szöveg színe'],
            'border_color' => ['Szegélyszín', ''],
            'link_color' => ['Link szín', ''],
        ];
        foreach ($colors as $key => $meta) {
            self::field_text($key, $meta[0], $options, $meta[1], ['class' => 'cmv2-color-picker']);
        }
        self::field_text('backdrop_color', 'Háttér árnyékolás', $options, 'RGBA érték, pl. <code>rgba(0,0,0,0.4)</code>');
        echo '</div>';
        self::card_close();

        self::card_open('Forma');
        ?>
        <div class="cmv2-field">
            <label for="cmv2_border_radius">Sarkok lekerekítése: <output id="cmv2-radius-out"><?php echo intval($options['border_radius']); ?></output> px</label>
            <input type="range" id="cmv2_border_radius" name="cmv2_border_radius" min="0" max="50" value="<?php echo esc_attr($options['border_radius']); ?>" />
        </div>
        <?php
        self::card_close();

        self::section_close();
    }

    private static function section_integrations($options)
    {
        self::section_open('integrations', 'Integrációk');

        self::card_open('Google Tag Manager', 'A konténer a default consent után töltődik be.');
        self::field_text('gtm_container_id', 'GTM Container ID', $options, '', ['placeholder' => 'GTM-XXXXXXX', 'class' => 'regular-text']);
        if (!empty($options['gtm_container_id'])) {
            echo '<div class="cmv2-callout cmv2-callout-warn"><strong>Ellenőrizd a GTM duplikációt.</strong> A plugin betölti a <code>'
                . esc_html($options['gtm_container_id'])
                . '</code> konténert. Ha kétszer töltődik be, nézd meg a téma <code>header.php</code>-ját és a többi plugint (MonsterInsights, Site Kit, WooCommerce). A beépített <code>_cmv2_gtm_loaded_*</code> guard csak a saját duplikációtól véd.</div>';
        }
        self::card_close();

        self::card_open('Google Ads konverziómodellezés');
        self::field_toggle('use_google_ads', 'Google Ads aktív', $options, 'Bekapcsolja az <code>url_passthrough</code> beállítást (gclid/gbraid URL-követés). Csak akkor kapcsold be, ha Google Ads konverziókövetést használsz. Érdemes GTM-ben Conversion Linker taget is használni.');
        self::field_text('wait_for_update_ms', 'wait_for_update (ms)', $options, 'Visszatérő látogatónál ennyi ideig vár a GTM a consent frissítésére. Minimum 500, ajánlott 1000.', ['type' => 'number', 'min' => 500, 'max' => 2000, 'step' => 100, 'class' => 'small-text']);
        self::card_close();

        self::card_open('Cloudflare Zaraz');
        self::field_toggle('use_zaraz', 'Zaraz consent kezelés', $options, 'Kapcsold be, ha Cloudflare Zaraz-t használsz (pl. Facebook Pixel).');
        ?>
        <div data-depends="cmv2_use_zaraz">
            <?php self::field_text('zaraz_purpose_name', 'Zaraz Purpose ID', $options,
                '<strong>Az ID-t add meg, nem a nevét, pontos kis- és nagybetűkkel.</strong> Cloudflare Dashboard → Zaraz → Settings → Consent → Purposes. Az ID case-sensitive: <code>kujo</code> ≠ <code>kujO</code>.',
                ['placeholder' => 'marketing', 'class' => 'regular-text']); ?>
        </div>
        <?php
        self::card_close();

        self::section_close();
    }

    private static function section_blocker($options)
    {
        self::section_open('blocker', 'Szkriptblokkoló');

        self::card_open('Szkriptek visszatartása hozzájárulásig', 'A jelölt szkriptek és iframe-ek csak a megfelelő kategória elfogadása után indulnak el.');
        self::field_toggle('blocker_enabled', 'Szkriptblokkoló bekapcsolása', $options, 'Csak azoknak a látogatóknak blokkol, akiknek a banner megjelenik (régiószűrés mellett a többiek mindent megkapnak).');
        ?>
        <div data-depends="cmv2_blocker_enabled">
            <?php self::field_textarea('blocker_rules', 'Automatikus szabályok', $options,
                'Soronként <code>kategória: minta</code>, ahol a kategória <code>analytics</code> vagy <code>ads</code>. A minta a bevarázsolt szkript <code>src</code> címében vagy handle-jében keresett szövegrészlet. A <code>#</code> kezdetű sor megjegyzés.',
                "ads: connect.facebook.net\nanalytics: static.hotjar.com"); ?>
        </div>
        <?php
        self::card_close();

        self::card_open('Kézi jelölés');
        ?>
        <p class="cmv2-help" style="margin:0">A kódba égetett (pl. a témába beillesztett) szkripteket és beágyazásokat kézzel kell jelölni. Az automatikus szabályok csak a WordPress által betöltött (enqueue) szkripteket látják.</p>
        <pre class="cmv2-code">&lt;script type="text/plain" data-cmv2-category="analytics" src="https://example.com/tracker.js"&gt;&lt;/script&gt;

&lt;iframe data-cmv2-category="ads" data-cmv2-src="https://www.youtube.com/embed/ID"&gt;&lt;/iframe&gt;</pre>
        <div class="cmv2-callout cmv2-callout-info">A már lefutott szkriptet a böngésző nem tudja visszavonni. Ha a látogató később visszavonja a hozzájárulást, a változás újratöltés után érvényesül.</div>
        <?php
        self::card_close();

        self::section_close();
    }

    private static function section_stats($options)
    {
        $days = isset($_GET['cmv2_days']) ? intval($_GET['cmv2_days']) : 30;
        $report = CMV2_Stats::get_report($days);
        $t = $report['totals'];
        $pct = function ($value) use ($t) {
            return $t['decisions'] > 0 ? round($value / $t['decisions'] * 100) . '%' : '–';
        };
        $base = admin_url('options-general.php?page=' . CMV2_MENU_SLUG);

        self::section_open('stats', 'Statisztika');

        if (empty($options['stats_enabled'])) {
            echo '<div class="cmv2-callout cmv2-callout-warn" style="margin-bottom:16px">A statisztika gyűjtése ki van kapcsolva (Általános szekció). Az alábbi adatok a korábban gyűjtöttek.</div>';
        }

        echo '<div class="cmv2-card"><div class="cmv2-card-head cmv2-stats-head"><h2>Utolsó ' . intval($report['days']) . ' nap</h2><div class="cmv2-range">';
        foreach ([7, 30, 90] as $range) {
            printf(
                '<a href="%s" class="%s">%d nap</a>',
                esc_url(add_query_arg('cmv2_days', $range, $base) . '#stats'),
                $range === $report['days'] ? 'is-active' : '',
                $range
            );
        }
        echo '</div></div><div class="cmv2-card-body">';

        $cards = [
            ['Banner megjelenés', $t['shown'], 'Munkamenetenként egyszer számolva'],
            ['Első döntés', $t['decisions'], 'Ennyi látogató választott először'],
            ['Mindent elfogad', $pct($t['accept_all']), $t['accept_all'] . ' döntés'],
            ['Csak szükséges', $pct($t['reject_all']), $t['reject_all'] . ' döntés'],
            ['Egyedi', $pct($t['partial']), $t['partial'] . ' döntés'],
            ['Későbbi módosítás', $t['change'], 'A megnyitó gombbal'],
        ];
        echo '<div class="cmv2-stat-cards">';
        foreach ($cards as $card) {
            printf(
                '<div class="cmv2-stat"><span>%s</span><strong>%s</strong><small>%s</small></div>',
                esc_html($card[0]),
                esc_html($card[1]),
                esc_html($card[2])
            );
        }
        echo '</div>';

        echo self::chart_svg($report); // Built from integers and escaped strings only

        echo '</div></div>';

        self::card_open('Adatkezelés');
        ?>
        <p class="cmv2-help" style="margin:0">Csak napi összesítések tárolódnak, a 180 napnál régebbiek automatikusan törlődnek. A gyűjtő végpont publikus, de védett (same-origin ellenőrzés, napi forgó token, napi plafon). Elszánt hamisítást ez sem zár ki, ezért a számok tájékoztató jellegűek.</p>
        <form method="post" action="" onsubmit="return window.confirm('Biztosan törlöd az összes statisztikát?');">
            <input type="hidden" name="<?php echo esc_attr(CMV2_NONCE_NAME); ?>" value="<?php echo esc_attr(wp_create_nonce(CMV2_NONCE_ACTION)); ?>" />
            <button type="submit" name="cmv2_reset_stats" value="1" class="button">Statisztika törlése</button>
        </form>
        <?php
        self::card_close();

        self::section_close();
    }

    /**
     * Stacked daily bars (accepted all / custom / necessary only) as inline SVG
     */
    private static function chart_svg($report)
    {
        $series = $report['series'];
        $n = count($series);
        $w = 720;
        $h = 220;
        $pad_left = 34;
        $pad_bottom = 22;
        $plot_w = $w - $pad_left;
        $plot_h = $h - $pad_bottom - 8;

        $max = 1;
        foreach ($series as $day) {
            $max = max($max, $day['accept_all'] + $day['partial'] + $day['reject_all']);
        }

        $slot = $plot_w / $n;
        $bar = max(2, $slot * 0.72);
        $colors = ['accept_all' => '#2271b1', 'partial' => '#dba617', 'reject_all' => '#8c8f94'];
        $labels = ['accept_all' => 'Mindent elfogad', 'partial' => 'Egyedi', 'reject_all' => 'Csak szükséges'];

        $svg = '<svg class="cmv2-chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Napi döntések megoszlása">';

        // Y axis: 0, half, max
        foreach ([0, 0.5, 1] as $ratio) {
            $y = 8 + $plot_h - $plot_h * $ratio;
            $svg .= '<line x1="' . $pad_left . '" x2="' . $w . '" y1="' . round($y, 1) . '" y2="' . round($y, 1) . '" stroke="#dcdcde" stroke-width="1"/>';
            $svg .= '<text x="' . ($pad_left - 6) . '" y="' . round($y + 4, 1) . '" text-anchor="end" font-size="10" fill="#646970">' . esc_html(round($max * $ratio)) . '</text>';
        }

        $i = 0;
        foreach ($series as $date => $day) {
            $x = $pad_left + $i * $slot + ($slot - $bar) / 2;
            $y = 8 + $plot_h;
            $title = $date;
            foreach (['accept_all', 'partial', 'reject_all'] as $key) {
                $title .= ' | ' . $labels[$key] . ': ' . $day[$key];
                if ($day[$key] > 0) {
                    $bh = $plot_h * $day[$key] / $max;
                    $y -= $bh;
                    $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($bar, 1) . '" height="' . round($bh, 1) . '" fill="' . $colors[$key] . '"><title>' . esc_html($title) . '</title></rect>';
                }
            }
            // Invisible hit area so empty days still show a tooltip
            $svg .= '<rect x="' . round($pad_left + $i * $slot, 1) . '" y="8" width="' . round($slot, 1) . '" height="' . $plot_h . '" fill="transparent"><title>' . esc_html($title) . '</title></rect>';
            $i++;
        }

        // X axis labels: first, middle, last
        $dates = array_keys($series);
        foreach ([0, intval(($n - 1) / 2), $n - 1] as $idx) {
            $x = $pad_left + $idx * $slot + $slot / 2;
            $anchor = $idx === 0 ? 'start' : ($idx === $n - 1 ? 'end' : 'middle');
            $svg .= '<text x="' . round($x, 1) . '" y="' . ($h - 6) . '" text-anchor="' . $anchor . '" font-size="10" fill="#646970">' . esc_html(substr($dates[$idx], 5)) . '</text>';
        }

        $svg .= '</svg>';

        $legend = '<ul class="cmv2-legend">';
        foreach ($colors as $key => $color) {
            $legend .= '<li><i style="background:' . $color . '"></i>' . esc_html($labels[$key]) . '</li>';
        }
        $legend .= '</ul>';

        return '<div class="cmv2-chart-wrap">' . $svg . $legend . '</div>';
    }

    private static function section_regions($options)
    {
        self::section_open('regions', 'Régiók');

        self::card_open('Régió-alapú viselkedés');
        self::field_toggle('eea_only_banner', 'Banner csak a megadott régióknak', $options, 'A banner csak az EEA/GDPR (vagy az alábbi egyedi) országokból érkező látogatóknak jelenik meg. Ha az ország nem azonosítható, a banner megjelenik.');
        ?>
        <div class="cmv2-callout cmv2-callout-info">Országdetekcióhoz Cloudflare (<code>CF-IPCountry</code>) vagy más proxy (<code>X-Country-Code</code>) szükséges.</div>
        <?php
        self::field_textarea('custom_regions', 'Egyedi régiólista', $options,
            'Vesszővel vagy sortöréssel elválasztott 2 betűs ISO-3166-1 kódok. <strong>Üresen</strong> az alapértelmezett EEA/GDPR lista él: ' . esc_html(implode(', ', CMV2_Options::default_regions())) . '.',
            'AT, BE, DE, HU, ...');
        self::card_close();

        self::section_close();
    }

    private static function section_status($options)
    {
        $checks = self::get_status_checks($options);
        $nonce = wp_create_nonce(CMV2_NONCE_ACTION);

        self::section_open('status', 'Állapot és mentés');

        self::card_open('Állapot');
        echo '<ul class="cmv2-checks">';
        foreach ($checks as $check) {
            printf(
                '<li class="cmv2-check cmv2-check-%s"><span class="dashicons dashicons-%s" aria-hidden="true"></span><div><strong>%s</strong><small>%s</small></div></li>',
                esc_attr($check['state']),
                esc_attr($check['state'] === 'ok' ? 'yes-alt' : ($check['state'] === 'warn' ? 'warning' : 'info-outline')),
                esc_html($check['label']),
                wp_kses($check['detail'], ['code' => []])
            );
        }
        echo '</ul>';
        self::card_close();

        self::card_open('Export / import', 'Mentsd le a beállításokat JSON fájlba, vagy tölts be egy korábbit.');
        ?>
        <form method="post" action="" enctype="multipart/form-data" class="cmv2-transfer">
            <input type="hidden" name="<?php echo esc_attr(CMV2_NONCE_NAME); ?>" value="<?php echo esc_attr($nonce); ?>" />
            <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr(wp_unslash($_SERVER['REQUEST_URI'])); ?>" />
            <div class="cmv2-transfer-row">
                <button type="submit" name="cmv2_export_settings" value="1" class="button">Beállítások exportálása</button>
            </div>
            <div class="cmv2-transfer-row">
                <input type="file" name="cmv2_import_file" accept=".json" />
                <button type="submit" name="cmv2_import_settings" value="1" class="button" id="cmv2-import-btn">Importálás</button>
            </div>
        </form>
        <?php
        self::card_close();

        self::section_close();
    }

    /**
     * Real configuration checks instead of a static "everything is active" box
     */
    private static function get_status_checks($options)
    {
        $checks = [];

        $checks[] = [
            'state' => !empty($options['gtm_container_id']) ? 'ok' : 'info',
            'label' => 'Google Tag Manager',
            'detail' => !empty($options['gtm_container_id'])
                ? 'Betöltve a pluginból: <code>' . esc_html($options['gtm_container_id']) . '</code>'
                : 'Nincs megadva. Rendben, ha a GTM-et más módon (téma, másik plugin) töltöd be.',
        ];

        $checks[] = [
            'state' => !empty($options['use_zaraz']) ? 'ok' : 'info',
            'label' => 'Cloudflare Zaraz',
            'detail' => !empty($options['use_zaraz'])
                ? 'Engedélyezve, purpose: <code>' . esc_html($options['zaraz_purpose_name']) . '</code>'
                : 'Kikapcsolva.',
        ];

        $country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])) : '';
        if ($country !== '') {
            $checks[] = ['state' => 'ok', 'label' => 'Országdetekció', 'detail' => 'A Cloudflare országheader elérhető (ez a kérés: <code>' . esc_html($country) . '</code>).'];
        } elseif (!empty($options['eea_only_banner'])) {
            $checks[] = ['state' => 'warn', 'label' => 'Országdetekció', 'detail' => 'A régiószűrés be van kapcsolva, de nem érkezett országheader, ezért a banner mindenkinek megjelenik.'];
        } else {
            $checks[] = ['state' => 'info', 'label' => 'Országdetekció', 'detail' => 'Nem szükséges, a régiószűrés ki van kapcsolva.'];
        }

        $checks[] = [
            'state' => 'info',
            'label' => 'Oldalcache',
            'detail' => (defined('WP_CACHE') && WP_CACHE)
                ? 'Oldalcache érzékelve. A consent cookie kiértékelése a böngészőben történik, így cache mellett is biztonságos.'
                : 'Nincs érzékelt oldalcache. A consent kiértékelése így is a böngészőben történik.',
        ];

        $checks[] = [
            'state' => 'info',
            'label' => 'Verzió',
            'detail' => 'Plugin <code>' . esc_html(CMV2_VERSION) . '</code>, consent séma <code>' . esc_html(CMV2_CONSENT_VERSION) . '</code>',
        ];

        return $checks;
    }

    // ------------------------------------------------------------------
    // Live preview
    // ------------------------------------------------------------------

    private static function preview($options)
    {
        ?>
        <aside class="cmv2-preview" aria-label="Élő előnézet">
            <div class="cmv2-preview-head">
                <strong>Élő előnézet</strong>
                <div class="cmv2-preview-views" role="tablist">
                    <button type="button" class="is-active" data-view="simple" role="tab" aria-selected="true">Egyszerű</button>
                    <button type="button" data-view="detailed" role="tab" aria-selected="false">Részletes</button>
                </div>
            </div>

            <div class="cmv2-stage" id="cmv2-stage" data-position="<?php echo esc_attr($options['popup_position']); ?>">
                <div class="cmv2-stage-page" aria-hidden="true">
                    <i></i><i></i><i></i>
                </div>
                <div class="cmv2p-backdrop"></div>
                <div class="cmv2p-window" id="cmv2p-window">
                    <h3 id="cmv2p-title"></h3>
                    <p id="cmv2p-desc"></p>
                    <p><a href="#" id="cmv2p-privacy" tabindex="-1"></a></p>

                    <div class="cmv2p-view" data-view="simple">
                        <div class="cmv2p-actions">
                            <span class="cmv2p-btn cmv2p-primary" id="cmv2p-accept"></span>
                            <span class="cmv2p-btn cmv2p-secondary" id="cmv2p-reject"></span>
                            <span class="cmv2p-btn cmv2p-secondary" id="cmv2p-customize"></span>
                        </div>
                    </div>

                    <div class="cmv2p-view" data-view="detailed" hidden>
                        <div class="cmv2p-rows">
                            <div class="cmv2p-row"><span id="cmv2p-necessary"></span><i class="cmv2p-check is-on"></i></div>
                            <div class="cmv2p-row"><span id="cmv2p-analytics"></span><i class="cmv2p-check"></i></div>
                            <div class="cmv2p-row"><span id="cmv2p-ads"></span><i class="cmv2p-check"></i></div>
                        </div>
                        <div class="cmv2p-actions">
                            <span class="cmv2p-btn cmv2p-primary" id="cmv2p-accept2"></span>
                            <span class="cmv2p-btn cmv2p-secondary" id="cmv2p-save"></span>
                        </div>
                    </div>
                </div>
                <span class="cmv2p-open" id="cmv2p-open"></span>
            </div>
            <p class="cmv2-preview-note">Közelítő előnézet. A tényleges megjelenést a téma betűtípusa kismértékben befolyásolhatja.</p>
        </aside>
        <?php
    }
}
