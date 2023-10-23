<?php
namespace mvbplugins\fslightbox;

include_once 'C:\Bitnami\wordpress-6.0.1-0\apps\wordpress\htdocs\wp-content\plugins\simple-lightbox-fslight\admin\pre-post-install.php';

/**
 * wrapper class for functions in included file
 */
class WrapPPIFunctions {

	public function save_settings_before_upgrade_callback( $return, $plugin ) {
		return \mvbplugins\fslightbox\save_settings_before_upgrade_callback( $return, $plugin );
	}

	public function restore_settings_after_upgrade_callback( $response, $hook_extra, $result ) {
		return \mvbplugins\fslightbox\restore_settings_after_upgrade_callback( $response, $hook_extra, $result );
	}

	public function savePluginFiles( $info ) {
		return \mvbplugins\fslightbox\savePluginFiles( $info );
	}

	public function restorePluginFiles() {
		return \mvbplugins\fslightbox\restorePluginFiles();
	}
}