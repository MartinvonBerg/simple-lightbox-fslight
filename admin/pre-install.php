<?php

/**
 *
 * Version:           1.5.0
 * Requires at least: 5.9
 * Requires PHP       7.3
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace mvbplugins\fslightbox;

if (!defined('ABSPATH')) {
    die('Are you ok?');
}

add_filter('upgrader_pre_install', '\mvbplugins\fslightbox\save_settings_before_upgrade_callback', 10, 2);

/**
 * handle pre install hook
 * @source https://stackoverflow.com/questions/56179399/wordpress-run-function-before-plugin-is-updating handle pre install hook
 * @param  [type] $return
 * @param  [type] $plugin
 * @return void
 */
function save_settings_before_upgrade_callback($return, $plugin)
{

    if (is_wp_error($return)) { //Bypass.
        return $return;
    }

    // When in cron (background updates) don't deactivate the plugin, as we require a browser to reactivate it
    if (wp_doing_cron()) {
        return $return;
    }

    $plugin = isset($plugin['plugin']) ? $plugin['plugin'] : '';
    if (empty($plugin)) {
        return new \WP_Error('bad_request', 'bad_request');
    }

    if (is_plugin_active($plugin)) {
        //You can play with plugin zip download over here
        //Deactivate the plugin silently, Prevent deactivation hooks from running.
        deactivate_plugins($plugin, true);
    }

    return $return;
}
