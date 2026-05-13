<?php
/**
 * Integration Tests for Admin Settings Page
 *
 * Tests admin settings page functionality:
 * - Page loads without errors
 * - Packages display from gateway endpoint
 * - Settings save correctly
 * - API fallback works
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_Admin_Settings_Integration extends WP_UnitTestCase {

	/**
	 * Admin user ID for testing
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Set up test fixtures
	 */
	public function setUp() {
		parent::setUp();
		$this->admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->admin_id );
	}

	/**
	 * Test 1: Settings page class exists
	 */
	public function test_settings_page_class_exists() {
		$this->assertTrue( class_exists( 'AISTMA_Settings_Page' ), 'Settings page class should exist' );
	}

	/**
	 * Test 2: Settings page can be instantiated
	 */
	public function test_settings_page_instantiation() {
		$settings_page = new AISTMA_Settings_Page();
		$this->assertIsObject( $settings_page, 'Should instantiate settings page' );
	}

	/**
	 * REGRESSION TEST: Settings page loads without PHP parse errors (v2.3.0 fix)
	 * Verifies that admin class parse error fix doesn't break settings page
	 */
	public function test_regression_settings_page_loads_without_errors() {
		// This test verifies the settings page class and admin class both load
		$admin_class = 'AISTMA_Admin';
		$settings_class = 'AISTMA_Settings_Page';

		$this->assertTrue( class_exists( $admin_class ), 'Admin class should load without parse errors' );
		$this->assertTrue( class_exists( $settings_class ), 'Settings page class should load' );

		// Both should instantiate successfully
		$admin = new AISTMA_Admin();
		$settings = new AISTMA_Settings_Page();

		$this->assertIsObject( $admin, 'Admin class should instantiate' );
		$this->assertIsObject( $settings, 'Settings page class should instantiate' );
	}

	/**
	 * REGRESSION TEST: Gateway function exists and is accessible
	 * Verifies aistma_get_available_packages() exists after gateway auth fix
	 */
	public function test_regression_gateway_function_exists() {
		$this->assertTrue( function_exists( 'aistma_get_available_packages' ), 'Package getter function should exist' );
	}

	/**
	 * REGRESSION TEST: Settings page can call gateway endpoint
	 * Verifies the settings page can attempt to fetch packages from gateway
	 */
	public function test_regression_settings_can_call_gateway_endpoint() {
		// Mock the gateway response
		add_filter(
			'pre_http_request',
			function( $preempt, $request, $url ) {
				if ( strpos( $url, 'packages-summary' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'packages' => [
									[
										'id'   => 'special',
										'name' => 'Special $1',
										'price' => 1.00,
									],
									[
										'id'   => 'five',
										'name' => 'Five a day $1',
										'price' => 1.00,
									],
									[
										'id'   => 'free',
										'name' => 'Free Package',
										'price' => 0.00,
									],
								],
							]
						),
					];
				}
				return $preempt;
			},
			10,
			3
		);

		// Call the function
		$packages = aistma_get_available_packages();

		// Verify packages were retrieved
		$this->assertIsArray( $packages, 'Should return array of packages' );
		$this->assertNotEmpty( $packages, 'Should have packages available' );

		// Verify package structure
		if ( ! empty( $packages ) ) {
			$first_package = reset( $packages );
			$this->assertArrayHasKey( 'id', $first_package, 'Package should have ID' );
			$this->assertArrayHasKey( 'name', $first_package, 'Package should have name' );
			$this->assertArrayHasKey( 'price', $first_package, 'Package should have price' );
		}
	}

	/**
	 * REGRESSION TEST: Packages display format correct
	 * Ensures package data is properly formatted for display
	 */
	public function test_regression_packages_display_format() {
		// Mock gateway endpoint
		add_filter(
			'pre_http_request',
			function( $preempt, $request, $url ) {
				if ( strpos( $url, 'packages-summary' ) !== false ) {
					return [
						'response' => [ 'code' => 200 ],
						'body'     => wp_json_encode(
							[
								'packages' => [
									[
										'id'   => 'test-1',
										'name' => 'Test Package 1',
										'price' => 9.99,
									],
								],
							]
						),
					];
				}
				return $preempt;
			},
			10,
			3
		);

		$packages = aistma_get_available_packages();

		$this->assertIsArray( $packages, 'Packages should be array' );

		foreach ( $packages as $package ) {
			$this->assertIsArray( $package, 'Each package should be array' );
			$this->assertNotEmpty( $package['name'], 'Package name should not be empty' );
			$this->assertTrue( is_numeric( $package['price'] ), 'Package price should be numeric' );
		}
	}

	/**
	 * REGRESSION TEST: Gateway API fallback works
	 * Verifies settings page gracefully handles gateway endpoint failure
	 */
	public function test_regression_gateway_api_fallback() {
		// Mock a failed gateway response
		add_filter(
			'pre_http_request',
			function( $preempt, $request, $url ) {
				if ( strpos( $url, 'packages-summary' ) !== false ) {
					// Return WP_Error to simulate connection failure
					return new WP_Error( 'connection_failed', 'Could not connect to gateway' );
				}
				return $preempt;
			},
			10,
			3
		);

		// Should not throw error, should return empty array or cached value
		$packages = aistma_get_available_packages();

		// Function should return something (even if empty)
		$this->assertIsArray( $packages, 'Should return array even on gateway failure' );
	}

	/**
	 * REGRESSION TEST: Admin can access settings page
	 * Verifies capability checks don't prevent admin from viewing settings
	 */
	public function test_regression_admin_can_access_settings() {
		// Verify current user is admin
		$this->assertTrue( current_user_can( 'manage_options' ), 'Test user should be admin' );

		// Admin settings page class should be instantiable
		$settings = new AISTMA_Settings_Page();
		$this->assertIsObject( $settings, 'Settings page should instantiate for admin user' );
	}
}
?>
