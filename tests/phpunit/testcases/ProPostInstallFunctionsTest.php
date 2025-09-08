<?php

use PHPUnit\Framework\TestCase;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Actions\expectDone;
use function Brain\Monkey\Filters\expectApplied;

final class ProPostInstallFunctionsTest extends TestCase {
	public function setUp(): void {
		parent::setUp();
		setUp();

	}
	public function tearDown(): void {
		tearDown();
		parent::tearDown();
	}

	public function test_restore_files_1() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->restorePluginFiles();
		$this->assertEquals( true, $result );
	}

	public function test_restore_files_2() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		expect( 'is_file' )
			->once()
			->andReturn( false );

		expect( 'is_dir' )
			->once()
			->andReturn( false );

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->restorePluginFiles();
		$this->assertEquals( false, $result );
	}

	public function test_restore_settings() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->restore_settings_after_upgrade_callback( '', '', [ 'error' ] );
		$this->assertEquals( [ 'error' ], $result );
	}

	public function test_restore_settings2() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		expect( 'is_file' )
			->once()
			->andReturn( false );

		expect( 'is_dir' )
			->once()
			->andReturn( false );

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->restore_settings_after_upgrade_callback( '', '', [ 'destination_name' => 'simple-lightbox-fslight' ] );
		$this->assertEquals( [ 'destination_name' => 'simple-lightbox-fslight' ], $result );
	}

	public function test_restore_settings3() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->restore_settings_after_upgrade_callback( '', [ '' => '' ], [ 'destination_name' => 'simple-lightbox-fslight' ] );
		$this->assertEquals( [ 'destination_name' => 'simple-lightbox-fslight' ], $result );
	}

	public function test_restore_settings4() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		expect( 'activate_plugin' )
			->once()
			->with( 'plugin' )
			->andReturn( 'WP_Error' );

		expect( 'is_wp_error' )
			->once()
			->with( 'WP_Error' )
			->andReturn( true );

		expect( 'add_action' )
			->once()
			->andReturn( true );

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->restore_settings_after_upgrade_callback( '', [ 'plugin' => 'plugin' ], [ 'destination_name' => 'simple-lightbox-fslight' ] );
		$this->assertEquals( [ 'destination_name' => 'simple-lightbox-fslight' ], $result );
	}

	public function test_save_files1() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->savePluginFiles( [] );
		$this->assertEquals( false, $result );
	}

	public function test_save_files2() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->savePluginFiles( [ 'temp_backup' => [ 'src' => '', 'slug' => '' ] ] );
		$this->assertEquals( false, $result );
	}

	public function test_save_files3() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->savePluginFiles( [ 'temp_backup' =>
			[ 'src' => 'C:\wamp64\www\wordpress\wp-content\plugins',
				'slug' => 'simple-lightbox-fslight'
			] ] );
		$this->assertEquals( true, $result );
	}

	public function test_save_files4() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		expect( 'is_file' )
			->once()
			->andReturn( false );

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->savePluginFiles( [ 'temp_backup' =>
			[ 'src' => PLUGIN_DIR . '',
				'slug' => 'simple-lightbox-fslight'
			] ] );
		$this->assertEquals( false, $result );
	}

	public function test_save_settings1() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		expect( 'is_wp_error' )
			->once()
			->with( 'WP_Error' )
			->andReturn( true );

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->save_settings_before_upgrade_callback( 'WP_Error', [ 'plugin' => 'plugin' ], [ 'destination_name' => 'simple-lightbox-fslight' ] );
		$this->assertEquals( 'WP_Error', $result );
	}

	public function test_save_settings2() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';

		expect( 'is_wp_error' )
			->once()
			->with( 'WP_Error' )
			->andReturn( false );

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->save_settings_before_upgrade_callback( 'WP_Error', [ 'plugin' => '', 'temp_backup' => [ 'slug' => 'simple-lightbox-fslight' ] ] );
		$this->assertEquals( 'WP_Error', $result );
	}

	public function test_save_settings3() {
		include_once PLUGIN_DIR . '\tests\src\WrapPrePostInstallFunctions.php';
		include_once 'C:\wamp64\www\wordpress\wp-includes\class-wp-error.php';

		expect( 'is_wp_error' )
			->once()
			->with( 'WP_Error' )
			->andReturn( false );

		expect( 'is_plugin_active' )
			->once()
			->andReturn( false );

		expect( 'activate_plugin' )
			->once()
			->andReturn( null );

		$tested = new mvbplugins\fslightbox\WrapPPIFunctions();
		$result = $tested->save_settings_before_upgrade_callback( 'WP_Error', [ 'plugin' => 'plugin', 'temp_backup' => [ 'slug' => 'simple-lightbox-fslight' ] ] );
		$result = (array) $result;
		$this->assertEquals( 'Update skipped. Could not save Plugin files (plugin-settings.json, fslightbox-paid).', $result["errors"]["bad_request"][0] );
	}
}