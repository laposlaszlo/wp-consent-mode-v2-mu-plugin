<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 * Removes settings, the statistics table and the scheduled cleanup.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cmv2_stats");
delete_option('cmv2_settings');
delete_option('cmv2_db_version');
wp_clear_scheduled_hook('cmv2_stats_cleanup');
