<?php
/**
 * Helper functions for the Plugin Update Process to backup and restore plugin-settings.json and fslightbox paid files.
 * Version:           2.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace mvbplugins\fslightbox;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Are you ok?' );
}

add_filter( 'upgrader_pre_install', '\mvbplugins\fslightbox\save_settings_before_upgrade_callback', 10, 2 );
add_filter( 'upgrader_post_install', '\mvbplugins\fslightbox\restore_settings_after_upgrade_callback', 10, 3 );

/**
 * handle pre install hook : save the settings to a seperate folder in WP-Plugin Directory.
 * @source https://stackoverflow.com/questions/56179399/wordpress-run-function-before-plugin-is-updating handle pre install hook
 * @param  mixed $return
 * @param  array $plugin
 * @return mixed $return
 */
function save_settings_before_upgrade_callback( $return, $plugin ) {
	/* $plugin = Array
												 (
													 [plugin] => simple-lightbox-fslight/simple-lightbox-fslight.php
													 [temp_backup] => Array
														 (
															 [slug] => simple-lightbox-fslight
															 [src] => C:\Bitnami\wordpress-6.0.1-0\apps\wordpress\htdocs/wp-content/plugins
															 [dir] => plugins
														 )

												 )
												 */
	$pluginUnmodiefied = $plugin;
	$slug = 'simple-lightbox-fslight';

	//Bypass on active WP-Errors.
	if ( \is_wp_error( $return ) ) {
		return $return;
	}

	// Bypass if not the intended plugin. Install all other Plugins regularly.
	if ( key_exists( 'temp_backup', $plugin ) && $plugin['temp_backup']['slug'] !== $slug ) {
		return $return;
	}

	// return with WP_Error if variable $plugin is not correct.
	$plugin = isset( $plugin['plugin'] ) ? $plugin['plugin'] : '';
	if ( empty( $plugin ) ) {
		return new \WP_Error( 'bad_request', 'bad_request' ); // The Plugin won't be updated with that response.
	}

	// When in cron (background updates) don't deactivate the plugin, as we require a browser to reactivate it. Plugin will be updated!
	if ( \is_plugin_active( $plugin ) && ! \wp_doing_cron() ) {
		//You can play with plugin zip download over here
		//Deactivate the plugin silently, Prevent deactivation hooks from running.
		\deactivate_plugins( $plugin, true );
	}

	// Now save the settings './plugin-settings.json' and the folder './js/fslightbox-paid'
	if ( isset( $pluginUnmodiefied['temp_backup']['slug'] ) && $pluginUnmodiefied['temp_backup']['slug'] === $slug ) {

		$success = savePluginFiles( $pluginUnmodiefied );

		if ( ! $success ) {
			\activate_plugin( $plugin );
			return new \WP_Error( 'bad_request', 'Update skipped. Could not save Plugin files.' );
		}
	}

	return $return;
}

/**
 * Restores the settings and js-paid files after an upgrade callback.
 *
 * @param mixed $response The response from the callback.
 * @param array $hook_extra The extra data from the callback.
 * @param mixed $result The result of the callback.
 * @return mixed The modified result.
 */
function restore_settings_after_upgrade_callback( $response, $hook_extra, $result ) {
	// check if plugin is simple-lightbox-fslight
	if ( key_exists( 'destination_name', $result ) && $result["destination_name"] === 'simple-lightbox-fslight' ) {

		$success = restorePluginFiles();

		if ( $success && key_exists( 'plugin', $hook_extra ) ) {
			$plugin = $hook_extra['plugin'];
			$success = \activate_plugin( $plugin );
		}
	}
	return $result;
}

/**
 * Saves the plugin files to a backup folder.
 *
 * @param array $info The information about the plugin and the backup.
 *                    - temp_backup: ['src' => string, 'slug' => string] The source path and slug of the backup.
 * @return bool True if the plugin files are successfully saved, false otherwise.
 */
function savePluginFiles( $info ) {
	$success = false;
	$destFolder = 'simple-lightbox-fslight-backup';
	$destFolder = \WP_PLUGIN_DIR . \DIRECTORY_SEPARATOR . $destFolder;

	if ( isset( $info['temp_backup']['src'] ) && isset( $info['temp_backup']['slug'] ) ) {
		$sourceFolder = $info['temp_backup']['src'] . \DIRECTORY_SEPARATOR . $info['temp_backup']['slug'] . \DIRECTORY_SEPARATOR;
	} else {
		return false;
	}

	// create directory
	if ( ! is_dir( $destFolder ) ) {
		$result = mkdir( $destFolder, 0777, true );
		if ( ! $result )
			return false;
	}

	// save the settings './plugin-settings.json'
	$path = $sourceFolder . 'plugin-settings.json';
	if ( \is_file( $path ) ) {
		$savePath = $destFolder . \DIRECTORY_SEPARATOR . 'plugin-settings.json';
		$success = xcopy( $path, $savePath );
	} else {
		return false;
	}

	// save the folder './js/fslightbox-paid
	$path = $sourceFolder . 'js/fslightbox-paid';
	if ( \is_dir( $path ) ) {
		$savePath = $destFolder . \DIRECTORY_SEPARATOR . '/fslightbox-paid';
		$success = xcopy( $path, $savePath );
	}

	return $success;
}

/**
 * Restores the plugin files.
 *
 * @return bool true if the plugin files are successfully restored, false otherwise.
 */
function restorePluginFiles() {
	$sourceFolder = \WP_PLUGIN_DIR . \DIRECTORY_SEPARATOR . 'simple-lightbox-fslight-backup';
	$destFolder = \WP_PLUGIN_DIR . \DIRECTORY_SEPARATOR . 'simple-lightbox-fslight';

	// check directories. All should be available. Does not work for first install.
	/*
												 if (!is_dir($sourceFolder) || !is_dir($destFolder)) {
													 return false;
												 }
												 */
	// restore the settings './plugin-settings.json'
	$path = $sourceFolder . \DIRECTORY_SEPARATOR . 'plugin-settings.json';
	if ( \is_file( $path ) ) {
		$savePath = $destFolder . \DIRECTORY_SEPARATOR . 'plugin-settings.json';
		$success = xcopy( $path, $savePath );
	}

	// restore the folder './js/fslightbox-paid
	$path = $sourceFolder . \DIRECTORY_SEPARATOR . 'fslightbox-paid';
	if ( \is_dir( $path ) ) {
		$savePath = $destFolder . \DIRECTORY_SEPARATOR . 'js/fslightbox-paid';
		$success = xcopy( $path, $savePath );
	}

	return true;
}

/**
 * Copy a file, or recursively copy a folder and its contents
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.1
 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @param       int      $permissions New folder creation permissions
 * @return      bool     Returns true on success, false on failure
 */
function xcopy( $source, $dest, $permissions = 0777 ) {
	$sourceHash = hashDirectory( $source );
	// Check for symlinks
	if ( is_link( $source ) && readlink( $source ) !== false ) {
		return symlink( readlink( $source ), $dest );
	}

	// Simple copy for a file
	if ( is_file( $source ) ) {
		return copy( $source, $dest );
	}

	// Make destination directory
	if ( ! is_dir( $dest ) ) {
		$result = mkdir( $dest, 0777, true );
		if ( ! $result )
			return false;
	}

	// Loop through the folder
	$dir = dir( $source );
	while ( false !== $entry = $dir->read() ) {
		// Skip pointers
		if ( $entry == '.' || $entry == '..' ) {
			continue;
		}

		// Deep copy directories
		if ( $sourceHash != hashDirectory( $source . "/" . $entry ) ) {
			xcopy( "$source/$entry", "$dest/$entry", $permissions );
		}
	}

	// Clean up
	$dir->close();
	return true;
}

/**
 * Recursively hashes the contents of a directory. In case of coping a directory inside itself, there is a need to hash check the directory otherwise and infinite loop of coping is generated.
 *
 * @param string $directory The path to the directory.
 * @return string|false The MD5 hash of the directory contents.
 */
function hashDirectory( $directory ) {
	if ( ! is_dir( $directory ) ) {
		return false;
	}

	$files = array();
	$dir = dir( $directory );

	while ( false !== ( $file = $dir->read() ) ) {
		if ( $file != '.' and $file != '..' ) {
			if ( is_dir( $directory . '/' . $file ) ) {
				$files[] = hashDirectory( $directory . '/' . $file );
			} else {
				$files[] = md5_file( $directory . '/' . $file );
			}
		}
	}

	$dir->close();

	return md5( implode( '', $files ) );
}