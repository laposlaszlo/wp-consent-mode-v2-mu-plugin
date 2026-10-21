<?php
/**
 * CMV2 Updater Class
 * GitHub Releases based updates (plugin-update-checker). Works with the regular
 * WordPress update UI, including the per-plugin "Enable auto-updates" toggle.
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMV2_Updater
{
    const REPO_URL = 'https://github.com/laposlaszlo/wp-consent-mode-v2-mu-plugin';
    const SLUG = 'wp-consent-mode-v2-mu-plugin';

    public static function init()
    {
        add_action('plugins_loaded', [__CLASS__, 'boot']);
    }

    public static function boot()
    {
        $loader = CMV2_PLUGIN_DIR . '/vendor/yahnis-elsts/plugin-update-checker/load-v5p6.php';
        if (!file_exists($loader)) {
            return; // Dependencies not installed
        }
        require_once $loader;

        if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
            return;
        }

        $checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            self::REPO_URL,
            CMV2_PLUGIN_FILE,
            self::SLUG
        );

        // Install the zip uploaded to the GitHub Release (built by the release workflow),
        // so the folder name always matches the plugin slug.
        $checker->getVcsApi()->enableReleaseAssets('/' . preg_quote(self::SLUG, '/') . '.*\.zip$/i');
        $checker->setBranch('main');

        // Private repository: define('CMV2_GITHUB_TOKEN', '...') in wp-config.php
        if (defined('CMV2_GITHUB_TOKEN') && CMV2_GITHUB_TOKEN) {
            $checker->setAuthentication(CMV2_GITHUB_TOKEN);
        }
    }
}
