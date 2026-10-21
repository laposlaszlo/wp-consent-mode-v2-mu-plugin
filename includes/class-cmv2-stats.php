<?php
/**
 * CMV2 Stats Class
 * Anonymous, aggregated consent statistics.
 *
 * Only daily counters are stored (day, event, analytics, ads, total). No IP address,
 * cookie, user id or user agent is recorded. This is statistics, not proof of consent.
 *
 * Events: shown (banner displayed), decision (first choice), change (later modification).
 *
 * The public endpoint is hardened (not tamper-proof) against abuse:
 * - same-origin check (Sec-Fetch-Site / Origin), filter `cmv2_stats_verify_origin`
 * - daily rotating HMAC token (today and yesterday are valid), embedded in the banner config
 * - plausibility caps per day, filter `cmv2_stats_daily_cap` for the absolute limit
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMV2_Stats
{
    const DB_VERSION = '1';
    const DB_OPTION = 'cmv2_db_version';
    const CRON_HOOK = 'cmv2_stats_cleanup';
    const RETENTION_DAYS = 180;
    const EVENTS = ['shown', 'decision', 'change'];

    public static function init()
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_action('admin_init', [__CLASS__, 'maybe_install']);
        add_action(self::CRON_HOOK, [__CLASS__, 'cleanup']);
    }

    public static function table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cmv2_stats';
    }

    /**
     * Create the table and schedule the retention cleanup
     */
    public static function install()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table();
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$table} (
            day date NOT NULL,
            event varchar(16) NOT NULL,
            analytics tinyint(1) NOT NULL DEFAULT 0,
            ads tinyint(1) NOT NULL DEFAULT 0,
            total bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (day,event,analytics,ads)
        ) {$charset};");

        update_option(self::DB_OPTION, self::DB_VERSION);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    /**
     * Also covers updates, which do not run the activation hook
     */
    public static function maybe_install()
    {
        if (get_option(self::DB_OPTION) !== self::DB_VERSION) {
            self::install();
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public static function register_routes()
    {
        register_rest_route('cmv2/v1', '/event', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_event'],
            // Public on purpose: the endpoint must work behind page caches without a nonce
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Daily rotating token. $days_ago = 0 is today, 1 is yesterday (UTC dates).
     * Cached pages older than two days send an expired token and are ignored.
     */
    public static function token($days_ago = 0)
    {
        $date = gmdate('Y-m-d', time() - intval($days_ago) * DAY_IN_SECONDS);
        return substr(hash_hmac('sha256', 'cmv2-stats|' . $date, wp_salt('auth')), 0, 32);
    }

    private static function is_valid_token($token)
    {
        if (!is_string($token) || $token === '') {
            return false;
        }
        return hash_equals(self::token(0), $token) || hash_equals(self::token(1), $token);
    }

    /**
     * Browsers send Sec-Fetch-Site (and Origin) themselves and page scripts cannot forge them,
     * so requests triggered from other sites are rejected. Clients that send neither pass here
     * and still have to present a valid token.
     */
    private static function is_origin_allowed($request)
    {
        if (!apply_filters('cmv2_stats_verify_origin', true)) {
            return true;
        }

        $fetch_site = $request->get_header('sec_fetch_site');
        if (is_string($fetch_site) && $fetch_site !== '') {
            return $fetch_site === 'same-origin';
        }

        $origin = $request->get_header('origin');
        if (is_string($origin) && $origin !== '') {
            $origin_host = wp_parse_url($origin, PHP_URL_HOST);
            $own_host = wp_parse_url(home_url(), PHP_URL_HOST);
            return $origin_host && $own_host && strtolower($origin_host) === strtolower($own_host);
        }

        return true;
    }

    /**
     * Counts of today's events by type
     */
    private static function today_totals()
    {
        global $wpdb;
        $table = self::table();

        $totals = ['shown' => 0, 'decision' => 0, 'change' => 0];
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT event, SUM(total) AS total FROM {$table} WHERE day = %s GROUP BY event",
            current_time('Y-m-d')
        ));
        foreach ((array) $rows as $row) {
            if (isset($totals[$row->event])) {
                $totals[$row->event] = (int) $row->total;
            }
        }
        return $totals;
    }

    /**
     * Rough sanity limits: decisions should not run far ahead of impressions, and
     * the total per day is capped. Generous on purpose so real traffic never hits them.
     */
    public static function is_plausible($event, $totals)
    {
        $cap = (int) apply_filters('cmv2_stats_daily_cap', 100000);
        if (array_sum($totals) >= $cap) {
            return false;
        }
        if ($event === 'decision') {
            return $totals['decision'] < max(50, $totals['shown'] * 3);
        }
        if ($event === 'change') {
            return $totals['change'] < max(100, ($totals['shown'] + $totals['decision']) * 3);
        }
        return true;
    }

    public static function handle_event($request)
    {
        $opts = cmv2_get_options();
        if (empty($opts['stats_enabled'])) {
            return new WP_REST_Response(null, 204);
        }

        if (!self::is_origin_allowed($request)) {
            return new WP_Error('cmv2_forbidden', 'Forbidden', ['status' => 403]);
        }

        $params = $request->get_json_params();
        if (!is_array($params)) {
            $params = $request->get_params();
        }

        if (!self::is_valid_token(isset($params['token']) ? $params['token'] : '')) {
            return new WP_Error('cmv2_forbidden', 'Forbidden', ['status' => 403]);
        }

        $event = isset($params['event']) ? (string) $params['event'] : '';
        if (!in_array($event, self::EVENTS, true)) {
            return new WP_Error('cmv2_invalid_event', 'Invalid event', ['status' => 400]);
        }

        $analytics = 0;
        $ads = 0;
        if ($event !== 'shown') {
            $analytics = !empty($params['analytics']) && filter_var($params['analytics'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $ads = !empty($params['ads']) && filter_var($params['ads'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        // Over the limit: drop silently so a sender learns nothing from the response
        if (self::is_plausible($event, self::today_totals())) {
            self::record($event, $analytics, $ads);
        }
        return new WP_REST_Response(null, 204);
    }

    /**
     * Atomic counter increment
     */
    public static function record($event, $analytics, $ads)
    {
        global $wpdb;
        $table = self::table();

        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (day, event, analytics, ads, total) VALUES (%s, %s, %d, %d, 1)
             ON DUPLICATE KEY UPDATE total = total + 1",
            current_time('Y-m-d'),
            $event,
            $analytics,
            $ads
        ));
    }

    /**
     * Build a per-day report for the last $days days (gaps filled with zeros)
     */
    public static function get_report($days)
    {
        global $wpdb;
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $table = self::table();

        $empty = ['shown' => 0, 'accept_all' => 0, 'partial' => 0, 'reject_all' => 0, 'change' => 0];
        $today = new DateTimeImmutable('today', wp_timezone());
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $series[$today->modify("-{$i} days")->format('Y-m-d')] = $empty;
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT day, event, analytics, ads, total FROM {$table} WHERE day >= %s",
            array_key_first($series)
        ));

        foreach ((array) $rows as $row) {
            if (!isset($series[$row->day])) {
                continue;
            }
            $count = (int) $row->total;
            if ($row->event === 'shown') {
                $series[$row->day]['shown'] += $count;
            } elseif ($row->event === 'change') {
                $series[$row->day]['change'] += $count;
            } elseif ($row->event === 'decision') {
                if ($row->analytics && $row->ads) {
                    $series[$row->day]['accept_all'] += $count;
                } elseif (!$row->analytics && !$row->ads) {
                    $series[$row->day]['reject_all'] += $count;
                } else {
                    $series[$row->day]['partial'] += $count;
                }
            }
        }

        $totals = $empty;
        foreach ($series as $day) {
            foreach ($empty as $key => $zero) {
                $totals[$key] += $day[$key];
            }
        }
        $totals['decisions'] = $totals['accept_all'] + $totals['partial'] + $totals['reject_all'];

        return ['days' => $days, 'series' => $series, 'totals' => $totals];
    }

    public static function reset()
    {
        global $wpdb;
        $table = self::table();
        $wpdb->query("TRUNCATE TABLE {$table}");
    }

    public static function cleanup()
    {
        global $wpdb;
        $table = self::table();
        $limit = (new DateTimeImmutable('today', wp_timezone()))->modify('-' . self::RETENTION_DAYS . ' days')->format('Y-m-d');
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE day < %s", $limit));
    }
}
