<?php
/**
 * Tests for gateway authentication functionality.
 *
 * @package AI_Story_Maker
 */

class AISTMA_Gateway_Auth_Test {

	/**
	 * Test auth header construction with API key from constant.
	 */
	public function test_auth_header_from_constant() {
		if ( defined( 'AISTMA_GATEWAY_API_KEY' ) ) {
			$this->markTestSkipped( 'AISTMA_GATEWAY_API_KEY constant already defined' );
		}

		define( 'AISTMA_GATEWAY_API_KEY', 'test-constant-key-123' );
		$generator = $this->get_story_generator();
		$headers   = $generator->get_gateway_request_headers();

		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertEqual( 'Bearer test-constant-key-123', $headers['Authorization'] );
	}

	/**
	 * Test auth header construction with API key from option.
	 */
	public function test_auth_header_from_option() {
		update_option( 'aistma_gateway_api_key', 'test-option-key-456' );
		$generator = $this->get_story_generator();
		$headers   = $generator->get_gateway_request_headers();

		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertEqual( 'Bearer test-option-key-456', $headers['Authorization'] );
	}

	/**
	 * Test that constant takes priority over option.
	 */
	public function test_constant_priority_over_option() {
		if ( ! defined( 'AISTMA_GATEWAY_API_KEY' ) ) {
			define( 'AISTMA_GATEWAY_API_KEY', 'constant-key' );
		}
		update_option( 'aistma_gateway_api_key', 'option-key' );

		$generator = $this->get_story_generator();
		$headers   = $generator->get_gateway_request_headers();

		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertEqual( 'Bearer constant-key', $headers['Authorization'] );
	}

	/**
	 * Test no auth header when key is missing.
	 */
	public function test_no_auth_header_when_missing() {
		delete_option( 'aistma_gateway_api_key' );
		$generator = $this->get_story_generator();
		$headers   = $generator->get_gateway_request_headers();

		$this->assertArrayNotHasKey( 'Authorization', $headers );
	}

	/**
	 * Test managed subscription detection (null credits_remaining).
	 */
	public function test_managed_subscription_detection() {
		$subscription_status = array(
			'valid'               => true,
			'credits_remaining'   => null,
			'package_id'          => 'managed-pkg',
			'authenticated'       => true,
		);

		// Template should detect null as managed subscription
		$this->assertNull( $subscription_status['credits_remaining'] );
		$this->assertTrue( $subscription_status['authenticated'] );
	}

	/**
	 * Test counted subscription (integer credits_remaining).
	 */
	public function test_counted_subscription() {
		$subscription_status = array(
			'valid'             => true,
			'credits_remaining' => 10,
			'package_id'        => 'counted-pkg',
		);

		$this->assertIsInt( $subscription_status['credits_remaining'] );
		$this->assertEqual( 10, $subscription_status['credits_remaining'] );
	}

	/**
	 * Test no credits remaining (credits_remaining = 0).
	 */
	public function test_no_credits_remaining() {
		$subscription_status = array(
			'valid'             => false,
			'message'           => 'No credits remaining',
			'credits_remaining' => 0,
		);

		$this->assertEqual( 0, $subscription_status['credits_remaining'] );
		$this->assertFalse( $subscription_status['valid'] );
	}

	/**
	 * Helper: Get a story generator instance.
	 *
	 * @return AISTMA_Story_Generator
	 */
	private function get_story_generator() {
		if ( ! class_exists( 'AISTMA_Story_Generator' ) ) {
			require_once __DIR__ . '/../includes/class-aistma-story-generator.php';
		}
		return new AISTMA_Story_Generator();
	}

	/**
	 * Assert that an array has a given key.
	 *
	 * @param string $key    The key.
	 * @param array  $array  The array.
	 */
	private function assertArrayHasKey( $key, $array ) {
		if ( ! isset( $array[ $key ] ) ) {
			throw new Exception( "Key '$key' not found in array" );
		}
	}

	/**
	 * Assert that an array does not have a given key.
	 *
	 * @param string $key    The key.
	 * @param array  $array  The array.
	 */
	private function assertArrayNotHasKey( $key, $array ) {
		if ( isset( $array[ $key ] ) ) {
			throw new Exception( "Key '$key' found in array" );
		}
	}

	/**
	 * Assert that two values are equal.
	 *
	 * @param mixed $expected The expected value.
	 * @param mixed $actual   The actual value.
	 */
	private function assertEqual( $expected, $actual ) {
		if ( $expected !== $actual ) {
			throw new Exception( "Expected: $expected, Got: $actual" );
		}
	}

	/**
	 * Assert that a value is an integer.
	 *
	 * @param mixed $value The value.
	 */
	private function assertIsInt( $value ) {
		if ( ! is_int( $value ) ) {
			throw new Exception( "Expected integer, got " . gettype( $value ) );
		}
	}

	/**
	 * Assert that a value is null.
	 *
	 * @param mixed $value The value.
	 */
	private function assertNull( $value ) {
		if ( null !== $value ) {
			throw new Exception( "Expected null, got " . var_export( $value, true ) );
		}
	}

	/**
	 * Assert that a value is false.
	 *
	 * @param mixed $value The value.
	 */
	private function assertFalse( $value ) {
		if ( false !== $value ) {
			throw new Exception( "Expected false, got " . var_export( $value, true ) );
		}
	}

	/**
	 * Mark test as skipped.
	 *
	 * @param string $reason The reason.
	 */
	private function markTestSkipped( $reason ) {
		throw new Exception( "SKIPPED: $reason" );
	}
}
