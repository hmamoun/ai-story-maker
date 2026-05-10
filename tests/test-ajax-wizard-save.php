<?php
/**
 * Integration Tests for AJAX: aistma_wizard_save
 *
 * Tests the story save AJAX endpoint:
 * - Post publishing
 * - Credit deduction (1 credit)
 * - Event logging
 * - Response data
 * - Error handling
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_AJAX_Wizard_Save extends WP_Ajax_UnitTestCase {

	/**
	 * Holds the user ID for testing
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Holds a draft post ID
	 *
	 * @var int
	 */
	private $draft_post_id;

	/**
	 * Setup
	 */
	public function setUp() {
		parent::setUp();
		$this->user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $this->user_id );

		// Grant credits
		AISTMA_Credits_Manager::add_credits( $this->user_id, 10 );

		// Create a draft post
		$this->draft_post_id = $this->factory->post->create( [
			'post_status' => 'draft',
			'post_author' => $this->user_id,
		] );
	}

	/**
	 * Test 1: aistma_wizard_save() publishes post
	 */
	public function test_wizard_save_publishes_post() {
		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $this->draft_post_id;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'], 'Save should succeed' );

		// Verify post is now published
		$post = get_post( $this->draft_post_id );
		$this->assertEquals( 'publish', $post->post_status, 'Post should be published' );
	}

	/**
	 * Test 2: Deducts 1 credit
	 */
	public function test_wizard_save_deducts_1_credit() {
		$initial_balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );

		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $this->draft_post_id;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$after_balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );

		$this->assertEquals( $initial_balance - 1, $after_balance, 'Should deduct exactly 1 credit' );
	}

	/**
	 * Test 3: Logs aistma_story_generated event
	 */
	public function test_wizard_save_logs_story_generated_event() {
		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $this->draft_post_id;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		// Check if hook was triggered
		$logged = did_action( 'aistma_story_generated' );
		$this->assertGreaterThan( 0, $logged, 'Should trigger aistma_story_generated hook' );
	}

	/**
	 * Test 4: Returns remaining credits
	 */
	public function test_wizard_save_returns_remaining_credits() {
		$initial_balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );

		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $this->draft_post_id;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertArrayHasKey( 'credits_remaining', $response['data'], 'Should return credits_remaining' );
		$this->assertEquals( $initial_balance - 1, $response['data']['credits_remaining'], 'Credits value should match' );
	}

	/**
	 * Test 5: Nonce verification
	 */
	public function test_wizard_save_validates_nonce() {
		$_POST['nonce']   = 'invalid-nonce';
		$_POST['post_id'] = $this->draft_post_id;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieFail $e ) {
			// Expected
			$this->assertTrue( true, 'Should fail with invalid nonce' );
			return;
		}

		$this->fail( 'Should reject invalid nonce' );
	}

	/**
	 * Test 6: Error handling - save failure
	 */
	public function test_wizard_save_error_handling() {
		// Try to save a non-existent post
		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = 99999; // Non-existent

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'], 'Should fail for non-existent post' );
		$this->assertIsString( $response['data']['message'], 'Should include error message' );
	}

	/**
	 * Test 7: User can't save other user's post
	 */
	public function test_wizard_save_rejects_other_user_post() {
		// Create post by different user
		$other_user  = $this->factory->user->create( [ 'role' => 'editor' ] );
		$other_post  = $this->factory->post->create( [
			'post_status' => 'draft',
			'post_author' => $other_user,
		] );

		// Current user tries to save it
		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $other_post;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'], 'Should reject saving other user\'s post' );
	}

	/**
	 * Test 8: Prevent deduction if insufficient credits
	 */
	public function test_wizard_save_prevents_deduction_if_insufficient_credits() {
		// Set user to 0 credits
		AISTMA_Credits_Manager::deduct_credits( $this->user_id, 10 );

		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $this->draft_post_id;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		// Should fail
		$this->assertFalse( $response['success'], 'Should fail without credits' );

		// Post should remain draft
		$post = get_post( $this->draft_post_id );
		$this->assertEquals( 'draft', $post->post_status, 'Post should remain draft' );
	}

	/**
	 * Test 9: Can save multiple posts in sequence
	 */
	public function test_wizard_save_multiple_posts() {
		// Create multiple drafts
		$post_1 = $this->factory->post->create( [
			'post_status' => 'draft',
			'post_author' => $this->user_id,
		] );
		$post_2 = $this->factory->post->create( [
			'post_status' => 'draft',
			'post_author' => $this->user_id,
		] );

		// Save first post
		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $post_1;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response1 = json_decode( $this->_last_response, true );
		$this->assertTrue( $response1['success'] );

		// Save second post
		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $post_2;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response2 = json_decode( $this->_last_response, true );
		$this->assertTrue( $response2['success'] );

		// Verify both published and credits deducted correctly
		$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 8, $balance, 'Should have deducted 2 credits total (10 - 2)' );
	}

	/**
	 * Test 10: Already published post can't be saved again
	 */
	public function test_wizard_save_published_post() {
		// Create a published post
		$published = $this->factory->post->create( [
			'post_status' => 'publish',
			'post_author' => $this->user_id,
		] );

		$initial_balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );

		$_POST['nonce']   = wp_create_nonce( 'aistma_wizard_nonce' );
		$_POST['post_id'] = $published;

		try {
			$this->_handleAjax( 'aistma_wizard_save' );
		} catch ( WPAjaxDieStopException $e ) {
			// Expected
		}

		$response = json_decode( $this->_last_response, true );

		// Could be: error, or could allow republishing
		// Implementation-dependent. Verify it's handled gracefully
		$this->assertIsArray( $response, 'Should return structured response' );

		// If it fails, credits shouldn't be deducted
		if ( ! $response['success'] ) {
			$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
			$this->assertEquals( $initial_balance, $balance, 'Credits should not be deducted on failure' );
		}
	}
}
?>
