<?php
/**
 * The following snippets uses `PLUGIN` to prefix
 * the constants and class names. You should replace
 * it with something that matches your plugin name.
 */
$plugin_main_dir = dirname(__DIR__, 2);
$plugin_rel_dir = 'wp-content/plugins/simple-lightbox-fslight';

// define test environment
define( 'PLUGIN_PHPUNIT', true );

// define fake ABSPATH
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() );
}
// define fake PLUGIN_ABSPATH
if ( ! defined( 'PLUGIN_ABSPATH' ) ) {
	define( 'PLUGIN_ABSPATH', $plugin_main_dir );
}

// define fake WP_PLUGIN_DIR
#if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', dirname(__DIR__, 3));
	echo WP_PLUGIN_DIR;
#}

if ( ! defined( 'WP_SITEURL' ) ) {
	define( 'WP_SITEURL', 'http://localhost/wordpress');
}

if ( ! defined( 'WP_PLUGIN_URL' ) ) {
	define( 'WP_PLUGIN_URL', WP_SITEURL . '/' . $plugin_rel_dir);
}

// change this if the plugin was moved to a different folder
define ( 'PLUGIN_DIR', 'C:\wamp64\www\wordpress\wp-content\plugins\simple-lightbox-fslight');

// $comp_path = "C:/Users/Martin von Berg/AppData/Roaming/Composer"; // TODO: get the global path
$comp_path = PLUGIN_DIR . "/classes/html5-dom-document-php";

require_once $comp_path . '/autoload.php';