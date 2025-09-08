<?php
namespace mvbplugins\fslightbox;

include_once PLUGIN_DIR . '\admin\pre-post-install.php';

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