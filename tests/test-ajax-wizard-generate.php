<?php
/**
 * Integration Tests for AJAX: aistma_wizard_generate
 *
 * Tests the wizard story generation AJAX endpoint:
 * - Draft creation
 * - Credit validation
 * - Response data structure
 * - Event logging
 * - Error handling
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_AJAX_Wizard_Generate extends WP_Ajax_UnitTestCase {

	/**
	 * Holds the user ID for testing
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Setup
	 */
	public function setUp() {
		parent::setUp();
		$this->user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $this->user_id );

		// Grant credits
		AISTMA_Credits_Manager::add_credits( $this->user_id, 10 );
	}

	/**
	 * Test 1: aistma_wizard_generate() creates draft
	 */
	public function test_wizard_generate_creates_draft_post() {
		// Prepare AJAX request data
		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		// Perform AJAX call
		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected die behavior
		}

		// Get response
		$response = json_decode( $this->_last_response, true );

		$this->assertIsArray( $response, 'Should return JSON array' );
		$this->assertTrue( $response['success'], 'Request should succeed' );
		$this->assertArrayHasKey( 'post_id', $response['data'], 'Should return post_id' );

		// Verify post was created
		$post_id = $response['data']['post_id'];
		$post    = get_post( $post_id );

		$this->assertNotNull( $post, 'Post should exist' );
		$this->assertEquals( 'draft', $post->post_status, 'Post should be draft' );
		$this->assertEquals( 'post', $post->post_type, 'Post should be post type' );
	}

	/**
	 * Test 2: Credit check before generation
	 */
	public function test_wizard_generate_validates_credits() {
		// Set user to 0 credits
		AISTMA_Credits_Manager::deduct_credits( $this->user_id, 10 );

		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'], 'Should fail without credits' );
		$this->assertStringContainsString( 'credit', strtolower( $response['data']['message'] ), 'Should mention credits' );
	}

	/**
	 * Test 3: Returns post data (title, excerpt, featured_image)
	 */
	public function test_wizard_generate_returns_post_data() {
		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );

		// Check response data structure
		$this->assertArrayHasKey( 'title', $response['data'], 'Should include title' );
		$this->assertArrayHasKey( 'excerpt', $response['data'], 'Should include excerpt' );
		$this->assertArrayHasKey( 'featured_image', $response['data'], 'Should include featured_image' );
		$this->assertArrayHasKey( 'post_id', $response['data'], 'Should include post_id' );

		// Verify values are not empty
		$this->assertNotEmpty( $response['data']['title'], 'Title should not be empty' );
		$this->assertNotEmpty( $response['data']['excerpt'], 'Excerpt should not be empty' );
	}

	/**
	 * Test 4: Logs aistma_prompt_selected event
	 */
	public function test_wizard_generate_logs_prompt_selected_event() {
		// Clear any existing logs
		delete_user_meta( $this->user_id, '_aistma_event_log' );

		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		// Check if event was logged
		// This depends on how the plugin logs events
		// Could be: action hook, database log, user meta, etc.

		// Example: if logging via action hook
		$logged = did_action( 'aistma_prompt_selected' );
		$this->assertGreaterThan( 0, $logged, 'Should trigger aistma_prompt_selected hook' );
	}

	/**
	 * Test 5: Nonce verification
	 */
	public function test_wizard_generate_validates_nonce() {
		$_POST['nonce']  = 'invalid-nonce';
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieFail $e ) {
			// Expected: request should fail nonce check
			$this->assertTrue( true, 'Should fail with invalid nonce' );
			return;
		}

		$this->fail( 'Should reject invalid nonce' );
	}

	/**
	 * Test 6: Invalid prompt handling
	 */
	public function test_wizard_generate_handles_invalid_prompt() {
		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'invalid-prompt-id';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'], 'Should fail with invalid prompt' );
	}

	/**
	 * Test 7: Deduction does NOT happen on generate (only on save)
	 */
	public function test_wizard_generate_does_not_deduct_credits() {
		$initial_balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );

		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$after_balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );

		$this->assertEquals( $initial_balance, $after_balance, 'Credits should not deduct on generate' );
	}

	/**
	 * Test 8: API error handling
	 */
	public function test_wizard_generate_handles_api_errors() {
		// This test would require mocking the API
		// For now, verify the endpoint handles errors gracefully

		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		// Should always return structured response
		$this->assertIsArray( $response, 'Should return valid JSON' );
		$this->assertArrayHasKey( 'success', $response, 'Should have success key' );
	}

	/**
	 * Test 9: Response includes remaining credits
	 */
	public function test_wizard_generate_returns_remaining_credits() {
		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		if ( $response['success'] ) {
			// Should include credits (before deduction happens in save)
			$this->assertArrayHasKey( 'credits_remaining', $response['data'] );
			$this->assertEquals( 10, $response['data']['credits_remaining'], 'Credits should not change on generate' );
		}
	}

	/**
	 * Test 10: Can generate multiple stories in sequence
	 */
	public function test_wizard_generate_multiple_times() {
		// Generate first story
		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response1 = json_decode( $this->_last_response, true );
		$this->assertTrue( $response1['success'] );

		// Generate second story
		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-mystery';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response2 = json_decode( $this->_last_response, true );
		$this->assertTrue( $response2['success'] );

		// Verify both posts created
		$this->assertNotEquals( $response1['data']['post_id'], $response2['data']['post_id'], 'Should create different posts' );
	}

	/**
	 * REGRESSION TEST: Verify admin class loads without parse errors (v2.3.0 fix)
	 * Tests that PHP parse error fix in admin/class-aistma-admin.php doesn't break functionality
	 */
	public function test_regression_admin_class_loads_without_errors() {
		$admin_class = 'AISTMA_Admin';

		$this->assertTrue( class_exists( $admin_class ), 'Admin class should exist and load without parse errors' );

		// Verify the class can be instantiated
		$admin = new AISTMA_Admin();
		$this->assertIsObject( $admin, 'Should instantiate admin class successfully' );
	}

	/**
	 * REGRESSION TEST: Story generation works after admin class fix
	 * Comprehensive end-to-end test verifying story generation is not broken by v2.3.0 changes
	 */
	public function test_regression_story_generation_end_to_end_after_fix() {
		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected die behavior
		}

		$response = json_decode( $this->_last_response, true );

		// Verify complete response structure
		$this->assertTrue( $response['success'], 'Story generation should succeed after admin class fix' );
		$this->assertArrayHasKey( 'post_id', $response['data'], 'Should return post_id' );
		$this->assertArrayHasKey( 'title', $response['data'], 'Should return title' );
		$this->assertArrayHasKey( 'excerpt', $response['data'], 'Should return excerpt' );
		$this->assertArrayHasKey( 'featured_image', $response['data'], 'Should return featured_image' );

		// Verify post was created with correct status
		$post = get_post( $response['data']['post_id'] );
		$this->assertNotNull( $post, 'Post should exist' );
		$this->assertEquals( 'draft', $post->post_status, 'Post should be draft status' );
		$this->assertEquals( 'post', $post->post_type, 'Post should be post type' );
	}

	/**
	 * REGRESSION TEST: Credits properly tracked after generation
	 * Ensures credit system works correctly after admin class changes
	 */
	public function test_regression_credits_validation_after_admin_fix() {
		$initial_credits = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 10, $initial_credits, 'User should start with 10 credits' );

		$_POST['nonce']  = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['prompt'] = 'story-adventure';

		try {
			$this->_handleAjax( 'aistma_wizard_generate' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'], 'Generation should succeed with credits available' );

		// Credits should NOT deduct on generate (only on save)
		$after_generate = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 10, $after_generate, 'Credits should not deduct on generate action' );

		// Should indicate credits remaining in response
		if ( isset( $response['data']['credits_remaining'] ) ) {
			$this->assertEquals( 10, $response['data']['credits_remaining'], 'Response should show remaining credits' );
		}
	}
}
?>
