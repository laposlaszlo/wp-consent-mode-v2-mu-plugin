<?php
/**
 * CMV2 Blocker Class
 * Holds back scripts and iframes until the visitor has consented to their category.
 *
 * Two ways to mark content:
 * - manually:  <script type="text/plain" data-cmv2-category="analytics|ads">
 *              <iframe data-cmv2-category="ads" data-cmv2-src="https://...">
 * - by rule:   enqueued scripts whose handle or src matches a configured pattern
 *              are rewritten automatically (script_loader_tag).
 *
 * Activation happens in assets/js/consent-blocker.js.
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMV2_Blocker
{
    const CATEGORIES = ['analytics', 'ads'];

    /** @var array|null Parsed rules, cached per request */
    private static $rules = null;

    public static function init()
    {
        if (is_admin()) {
            return;
        }

        add_filter('script_loader_tag', [__CLASS__, 'filter_script_tag'], 10, 3);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets'], 20);
    }

    /**
     * Parse "category: pattern" lines. Empty lines and lines starting with # are ignored.
     */
    public static function parse_rules($raw)
    {
        $rules = [];
        foreach (preg_split('/\R/', (string) $raw) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (preg_match('/^(analytics|ads)\s*:\s*(.+)$/i', $line, $m)) {
                $pattern = trim($m[2]);
                if ($pattern !== '') {
                    $rules[] = ['category' => strtolower($m[1]), 'pattern' => $pattern];
                }
            }
        }
        return $rules;
    }

    private static function get_rules($opts)
    {
        if (self::$rules === null) {
            self::$rules = self::parse_rules(isset($opts['blocker_rules']) ? $opts['blocker_rules'] : '');
        }
        return self::$rules;
    }

    /**
     * Blocking only applies when the visitor would be shown the banner.
     */
    private static function is_active($opts)
    {
        return !empty($opts['blocker_enabled']) && CMV2_Frontend::is_banner_needed($opts);
    }

    /**
     * Rewrite matching enqueued scripts (including their inline before/after parts,
     * which WordPress passes in the same $tag string) to inert text/plain scripts.
     */
    public static function filter_script_tag($tag, $handle, $src)
    {
        $opts = cmv2_get_options();
        if (empty($opts['blocker_enabled']) || strpos($handle, 'cmv2-') === 0) {
            return $tag;
        }

        $haystack = $handle . ' ' . (string) $src;
        $category = '';
        foreach (self::get_rules($opts) as $rule) {
            if (stripos($haystack, $rule['pattern']) !== false) {
                $category = $rule['category'];
                break;
            }
        }
        if ($category === '' || !self::is_active($opts)) {
            return $tag;
        }

        return preg_replace_callback('/<script\b([^>]*)>/i', function ($m) use ($category) {
            $attrs = $m[1];

            // Leave data blocks (JSON, JSON-LD) alone
            if (preg_match('/\btype\s*=\s*([\'"])application\/(?:ld\+)?json\1/i', $attrs)) {
                return $m[0];
            }

            // Remember a non-default type (e.g. module) so it can be restored on activation
            $original = '';
            if (preg_match('/\stype\s*=\s*([\'"])(.*?)\1/i', $attrs, $t)) {
                $original = strtolower($t[2]);
                $attrs = preg_replace('/\stype\s*=\s*([\'"]).*?\1/i', '', $attrs);
            }

            $extra = '';
            if ($original !== '' && $original !== 'text/javascript' && $original !== 'application/javascript') {
                $extra = ' data-cmv2-type="' . esc_attr($original) . '"';
            }

            return '<script type="text/plain" data-cmv2-category="' . esc_attr($category) . '"' . $extra . $attrs . '>';
        }, $tag);
    }

    public static function enqueue_assets()
    {
        $opts = cmv2_get_options();
        if (empty($opts['blocker_enabled'])) {
            return;
        }

        wp_enqueue_script(
            'cmv2-blocker-js',
            CMV2_PLUGIN_URL . '/assets/js/consent-blocker.js',
            [],
            CMV2_VERSION,
            true
        );

        wp_localize_script('cmv2-blocker-js', 'CMV2_BLOCKER_CONFIG', [
            'cookie' => CMV2_LS_KEY,
            'version' => CMV2_CONSENT_VERSION,
            'ttl_days' => max(1, intval($opts['ttl_days'])),
            // Visitors outside the banner region are not asked, so nothing is held back for them
            'allow_all' => !CMV2_Frontend::is_banner_needed($opts),
        ]);
    }
}
