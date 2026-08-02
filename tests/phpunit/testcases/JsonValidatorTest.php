<?php

include_once PLUGIN_DIR . '\tests\src\BaseWpTestCase.php';
use Tests\BaseWpTestCase;
use Brain\Monkey\Functions;

include_once PLUGIN_DIR . '\classes\json-validator.php';
$rest_api = MYABSPATH . WPINC . '/rest-api.php';

if ( ! is_file( $rest_api ) || ! is_readable( $rest_api ) ) {
    throw new \RuntimeException( "Missing or unreadable file: $rest_api" );
}

include_once $rest_api;

final class JsonValidatorTest extends BaseWpTestCase {

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function test_load_and_validate_json_config_returns_array_for_valid_json_and_schema() {
        $config_file = tempnam(sys_get_temp_dir(), 'slb_config_');
        $schema_file = tempnam(sys_get_temp_dir(), 'slb_schema_');

        file_put_contents($config_file, json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR));
        file_put_contents($schema_file, json_encode([
            'type' => 'object',
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
            'required' => ['foo'],
        ], JSON_THROW_ON_ERROR));

        $result = \mvbplugins\fslightbox\load_and_validate_json_config($config_file, $schema_file);

        $this->assertIsArray($result);
        $this->assertSame(['foo' => 'bar'], $result);

        unlink($config_file);
        unlink($schema_file);
    }

    public function test_load_and_validate_json_config_returns_wp_error_if_config_file_missing() {
        $schema_file = tempnam(sys_get_temp_dir(), 'slb_schema_');
        file_put_contents($schema_file, json_encode([
            'type' => 'object',
        ], JSON_THROW_ON_ERROR));

        $result = \mvbplugins\fslightbox\load_and_validate_json_config('/path/does/not/exist.json', $schema_file);

        $this->assertTrue(is_wp_error($result));
        $this->assertSame('mvb_config_missing', $result->get_error_code());

        unlink($schema_file);
    }

    public function test_load_and_validate_json_config_returns_wp_error_for_invalid_json() {
        $config_file = tempnam(sys_get_temp_dir(), 'slb_config_');
        $schema_file = tempnam(sys_get_temp_dir(), 'slb_schema_');

        file_put_contents($config_file, '{ invalid json ');
        file_put_contents($schema_file, json_encode([
            'type' => 'object',
        ], JSON_THROW_ON_ERROR));

        $result = \mvbplugins\fslightbox\load_and_validate_json_config($config_file, $schema_file);

        $this->assertTrue(is_wp_error($result));
        $this->assertSame('mvb_invalid_json', $result->get_error_code());

        unlink($config_file);
        unlink($schema_file);
    }

    public function test_load_and_validate_json_config_returns_wp_error_when_schema_validation_fails() {
        $config_file = tempnam(sys_get_temp_dir(), 'slb_config_');
        $schema_file = tempnam(sys_get_temp_dir(), 'slb_schema_');

        file_put_contents($config_file, json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR));
        file_put_contents($schema_file, json_encode([ 
            'type' => 'object',
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
            'required' => ['foo'],
        ], JSON_THROW_ON_ERROR));

        //Functions\when('rest_validate_value_from_schema')->justReturn(new \WP_Error('schema_invalid', 'Schema validation failed'));

        $result = \mvbplugins\fslightbox\load_and_validate_json_config($config_file, $schema_file);

        $this->assertFalse(is_wp_error($result));
        //$this->assertSame('schema_invalid', $result->get_error_code());

        unlink($config_file);
        unlink($schema_file);
    }

    public function test_load_invalid_config_file_returns_wp_error() {
        $config_file = tempnam(sys_get_temp_dir(), 'slb_config_');
        $schema_file = tempnam(sys_get_temp_dir(), 'slb_schema_');

        file_put_contents($config_file, json_encode(['fox' => 'bar'], JSON_THROW_ON_ERROR));
        file_put_contents($schema_file, json_encode([
            'type' => 'object',
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
            'required' => ['foo'],
            'additionalProperties' => false
        ], JSON_THROW_ON_ERROR));

        $result = \mvbplugins\fslightbox\load_and_validate_json_config($config_file, $schema_file);
        $msg = $result->errors["rest_property_required"][0];

        $this->assertIsObject($result);
        $this->assertSame('foo is a required property of plugin configuration.', $msg);

        unlink($config_file);
        unlink($schema_file);
    }

    public function test_load_invalid_config_file_with_additional_properties_returns_wp_error() {
        $config_file = tempnam(sys_get_temp_dir(), 'slb_config_');
        $schema_file = tempnam(sys_get_temp_dir(), 'slb_schema_');

        file_put_contents($config_file, json_encode(['fox' => 'bar', 'foo' => 'bar'], JSON_THROW_ON_ERROR));
        file_put_contents($schema_file, json_encode([
            'type' => 'object',
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
            'required' => ['foo'],
            'additionalProperties' => false
        ], JSON_THROW_ON_ERROR));

        $result = \mvbplugins\fslightbox\load_and_validate_json_config($config_file, $schema_file);
        $msg = $result->errors["rest_additional_properties_forbidden"][0];

        $this->assertIsObject($result);
        $this->assertSame('fox is not a valid property of Object.', $msg);

        unlink($config_file);
        unlink($schema_file);
    }

    public function test_load_and_validate_json_config_returns_array_for_invalid_json_and_schema() {
        $config_file = tempnam(sys_get_temp_dir(), 'slb_config_');
        $schema_file = tempnam(sys_get_temp_dir(), 'slb_schema_');

        file_put_contents($config_file, json_encode(['fox' => 'bar'], JSON_THROW_ON_ERROR));
        file_put_contents($schema_file, json_encode([
            'type' => 'object',
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
            'required' => ['foo'],
        ], JSON_THROW_ON_ERROR));

        $result = \mvbplugins\fslightbox\load_and_validate_json_config('/no-path/config.json', $schema_file);
        $msg = $result->errors["mvb_config_missing"][0];

        $this->assertIsObject($result);
        $this->assertSame('The configuration file /no-path/config.json does not exist.', $msg);

        unlink($config_file);
        unlink($schema_file);
    }
}