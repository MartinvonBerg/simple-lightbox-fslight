<?php
/**
 * Helper functions for the Plugin Update Process to backup and restore plugin-settings.json and fslightbox paid files.
 * Version:           3.3.0
 * Requires at least: 5.9
 * Requires PHP:      8.0
 * Author:            Martin von Berg
 * Author URI:        https://www.berg-reise-foto.de/software-wordpress-lightroom-plugins/wordpress-plugins-fotos-und-gpx/
 * License:           GPL-2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace mvbplugins\fslightbox;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Are you ok?' );
}

// note: a deactivated plugin will not backup and restore the settings and fslightbox paid files
add_filter( 'upgrader_pre_install', '\mvbplugins\fslightbox\save_settings_before_upgrade_callback', 10, 2 );
add_filter( 'upgrader_post_install', '\mvbplugins\fslightbox\restore_settings_after_upgrade_callback', 10, 3 );
add_filter( 'upgrader_source_selection', '\mvbplugins\fslightbox\save_settings_before_uploaded_zip_callback', 10, 4 );

/**
 * handle pre install hook : save the settings to a seperate folder in WP-Plugin Directory. 
 * Only report an error and skip Plugin update if saving of files fails. The original plugin will not be updated.
 * 
 * @source https://stackoverflow.com/questions/56179399/wordpress-run-function-before-plugin-is-updating handle pre install hook
 * @param  mixed $return The return value from the previous function (type is actually unknown)
 * @param  array $plugin An array that stores information about the updated plugin
 * @return mixed $return 
 */
function save_settings_before_upgrade_callback( mixed $return, array $plugin ): mixed {
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
	$slug = 'simple-lightbox-fslight'; // expected slug shall be the slug given by wordpress.org. 
	// using this: $slug = plugin_basename( __FILE__ ) would give a valid slug for every plugin. So the code would run for every plugin.

	//Bypass on active WP-Errors.
	if ( \is_wp_error( $return ) ) {
		return $return;
	}

	// Do only for the intended plugin. Install all other Plugins regularly and skip this if.
	if ( isset( $plugin['plugin'], $plugin['temp_backup']['slug'] ) && $plugin['temp_backup']['slug'] === $slug ) {

		// Now save the settings './plugin-settings.json' and the folder './js/fslightbox-paid'
		$success = savePluginFiles( $pluginUnmodiefied );

		if ( ! $success ) {
			return new \WP_Error( 'bad_request', 'Update skipped. Could not save Plugin files (plugin-settings.json, fslightbox-paid).' );
		}
	}

	return $return;
}

/**
 * Restores the settings and js-paid files after an upgrade callback. 
 * Will report an error and restore the original plugin ONLY if it the update is triggered from wordpress.org.
 * BUT will NOT restore the settings if the plugin is installed from an uploaded ZIP file!
 *
 * @param bool $response The response from the callback.
 * @param array $hook_extra The extra data from the callback.
 * @param array $result The result of the callback.
 * @return bool The unchanged result.
 */
function restore_settings_after_upgrade_callback( bool $response, array $hook_extra, array $result ): bool|\WP_Error {
	// check if plugin is simple-lightbox-fslight
	if ( key_exists( 'destination_name', $result ) && $result["destination_name"] === 'simple-lightbox-fslight' ) {

		$success = restorePluginFiles();

		if ( ! $success ) {
			return new \WP_Error( 'fslight_restore_failed', 'The plugin was installed, but customized files could not be restored. Restore manually if updated from ZIP-File' );
		}
	}
	return $response;
}

/**
 * Saves locally modified plugin files before an uploaded ZIP overwrites the installed plugin.^
 * Only report an error and skip Plugin update if saving of files fails. The original plugin will not be updated.
 *
 * @param string|\WP_Error $source        Path to the unpacked package.
 * @param string           $remote_source Temporary extraction directory.
 * @param \WP_Upgrader     $upgrader      Upgrader instance.
 * @param array            $hook_extra    Additional installation data.
 *
 * @return string|\WP_Error Unchanged package source or an error.
 */
function save_settings_before_uploaded_zip_callback( string|\WP_Error $source, string $remote_source, \WP_Upgrader $upgrader, array $hook_extra ): string|\WP_Error 
{
	if ( \is_wp_error( $source ) ) {
		return $source;
	}

	/*
	 * Repository updates are already handled by upgrader_pre_install().
	 * This callback is intended only for uploaded/plugin-install packages.
	 */
	if ( ( $hook_extra['type'] ?? '' ) !== 'plugin' || ( $hook_extra['action'] ?? '' ) !== 'install' ) {
		return $source;
	}

	$plugin_file = find_plugin_main_file_in_package( $source );

	if ( null === $plugin_file ) {
		return $source;
	}

	$plugin_data = \get_plugin_data( $plugin_file, false, false );

	/*
	 * Use a stable identifier from the uploaded package.
	 *
	 * Plugin Name alone is not technically guaranteed to be unique,
	 * but is sufficient here when combined with the expected folder.
	 */
	if ( $plugin_data['Name'] !== 'Simple Lightbox Fslight' ) {
		return $source;
	}

	$installed_folder = \WP_PLUGIN_DIR . \DIRECTORY_SEPARATOR . 'simple-lightbox-fslight';

	/*
	 * No existing installation: this is a first installation, not an update.
	 */
	if ( ! \is_dir( $installed_folder ) ) {
		return $source;
	}

	$success = savePluginFilesFromDirectory( $installed_folder );

	if ( ! $success ) {
		return new \WP_Error( 'fslight_backup_failed', 'Update INCORRECT. Could not save Simple Lightbox Fslight files.' );
	}

	return $source;
}

/**
 * Saves customized files from an installed plugin directory.
 *
 * @param string $source_folder Absolute plugin directory.
 * @return bool True on success.
 */
function savePluginFilesFromDirectory( string $source_folder ): bool {
    $source_folder = untrailingslashit( $source_folder );

    if ( ! \is_dir( $source_folder ) ) {
        return false;
    }

    $destination_folder = \WP_PLUGIN_DIR . \DIRECTORY_SEPARATOR . 'simple-lightbox-fslight-backup';

    if ( ! \is_dir( $destination_folder ) ) {
        $directory_permissions = \defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : 0755;

        if ( ! \mkdir( $destination_folder, $directory_permissions, true ) ) {
            return false;
        }
    }

    $settings_source = $source_folder . \DIRECTORY_SEPARATOR . 'plugin-settings.json';

    if ( ! \is_file( $settings_source ) ) {
        return false;
    }

    $settings_destination = $destination_folder . \DIRECTORY_SEPARATOR . 'plugin-settings.json';

    if ( ! xcopy( $settings_source, $settings_destination ) ) {
        return false;
    }

    $paid_source = $source_folder . \DIRECTORY_SEPARATOR . 'js' . \DIRECTORY_SEPARATOR . 'fslightbox-paid';

    if ( \is_dir( $paid_source ) ) {
        $paid_destination = $destination_folder . \DIRECTORY_SEPARATOR . 'fslightbox-paid';

        if ( ! xcopy( $paid_source, $paid_destination ) ) {
            return false;
        }
    }

    return true;
}

/**
 * Finds the main plugin file in an unpacked package.
 *
 * @param string $source Path selected by the upgrader.
 * @return string|null Absolute path to the plugin file.
 */
function find_plugin_main_file_in_package( string $source ): ?string {
    if ( ! \is_dir( $source ) ) {
        return null;
    }

    $files = glob( trailingslashit( $source ) . '*.php' );

    if ( false === $files ) {
        return null;
    }

    foreach ( $files as $file ) {
        $plugin_data = \get_plugin_data( $file, false, false );

        if ( ! empty( $plugin_data['Name'] ) ) {
            return $file;
        }
    }

    return null;
}

/**
 * Saves the plugin files to a backup folder.
 *
 * @param array $info The information about the plugin and the backup.
 *                    - temp_backup: ['src' => string, 'slug' => string] The source path and slug of the backup.
 * @return bool True if the plugin files are successfully saved, false otherwise.
 */
function savePluginFiles( array $info ) : bool {
	$success = false;
	$destFolder = \WP_PLUGIN_DIR . \DIRECTORY_SEPARATOR . 'simple-lightbox-fslight-backup';

	if ( isset( $info['temp_backup']['src'] ) && isset( $info['temp_backup']['slug'] ) ) {
		$sourceFolder = $info['temp_backup']['src'] . \DIRECTORY_SEPARATOR . $info['temp_backup']['slug'] . \DIRECTORY_SEPARATOR;
	} else {
		return false;
	}

	// create directory
	if ( ! is_dir( $destFolder ) ) {
		$directory_permissions = \defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : 0755;
		$result = mkdir( $destFolder, $directory_permissions, true );
		if ( ! $result ) {
			return false;
		}
	}

	// save the settings './plugin-settings.json'
	$path = $sourceFolder . 'plugin-settings.json';
	if ( \is_file( $path ) ) {
		$savePath = $destFolder . \DIRECTORY_SEPARATOR . 'plugin-settings.json';
		$success = xcopy( $path, $savePath );
	} else {
		return false;
	}

	// Save the optional folder and its contents './js/fslightbox-paid' if it exists.
	$path = $sourceFolder . 'js/fslightbox-paid';
	if ( \is_dir( $path ) ) {
		$savePath = $destFolder . \DIRECTORY_SEPARATOR . 'fslightbox-paid';
		$success = $success && xcopy( $path, $savePath );
	}

	return $success;
}

/**
 * Restores the plugin files.
 *
 * @return bool true if the plugin files are successfully restored, false otherwise.
 */
function restorePluginFiles() : bool {
	$sourceFolder = \WP_PLUGIN_DIR . \DIRECTORY_SEPARATOR . 'simple-lightbox-fslight-backup';
	$destFolder = \WP_PLUGIN_DIR . \DIRECTORY_SEPARATOR . 'simple-lightbox-fslight';
	$success = false;

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
		$success = $success && xcopy( $path, $savePath );
	}

	return $success;
}

/**
 * Copies a file or recursively copies a directory and its contents.
 *
 * @param string $source Source path.
 * @param string $dest   Destination path.
 *
 * @return bool True on success, false on failure.
 */
function xcopy( $source, $dest ) {
	$sourceHash = hashDirectory( $source );

	// Check for symlinks.
	if ( is_link( $source ) && false !== readlink( $source ) ) {
		return symlink( readlink( $source ), $dest );
	}

	// Simple copy for a file.
	if ( is_file( $source ) ) {
		return copy( $source, $dest );
	}

	// Make destination directory.
	if ( ! is_dir( $dest ) ) {
		$directory_permissions = \defined( 'FS_CHMOD_DIR' )
			? FS_CHMOD_DIR
			: 0755;

		if ( ! mkdir( $dest, $directory_permissions, true ) ) {
			return false;
		}
	}

	// Loop through the folder.
	$dir = dir( $source );

	if ( false === $dir ) {
		return false;
	}

	while ( false !== ( $entry = $dir->read() ) ) {
		// Skip pointers.
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}

		// Deep copy directories and files.
		if ( $sourceHash !== hashDirectory( $source . '/' . $entry ) ) {
			if (
				! xcopy(
					$source . '/' . $entry,
					$dest . '/' . $entry
				)
			) {
				$dir->close();
				return false;
			}
		}
	}

	$dir->close();

	return true;
}

/**
 * Recursively hashes the contents of a directory. In case of coping a directory inside itself, there is a need to hash check the directory otherwise and infinite loop of coping is generated.
 *
 * @param string $directory The path to the directory.
 * @return string|false The MD5 hash of the directory contents.
 */
function hashDirectory( string $directory ) : string|false {
	if ( ! is_dir( $directory ) ) {
		return false;
	}

	$files = [];
	$dir = dir( $directory );

	if ( false === $dir ) {
		return false;
	}

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