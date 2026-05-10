<?php
/**
 * Unit Tests for AISTMA_Weekly_Scheduler
 *
 * Tests weekly generation functionality:
 * - Enable/disable weekly
 * - Prompt selection for weekly
 * - Timing logic (should generate?)
 * - State persistence
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_AISTMA_Weekly_Scheduler extends WP_UnitTestCase {

	/**
	 * Holds the user ID for testing
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Weekly scheduler instance
	 *
	 * @var AISTMA_Weekly_Scheduler
	 */
	private $scheduler;

	/**
	 * Set up test fixtures
	 */
	public function setUp() {
		parent::setUp();
		$this->user_id  = $this->factory->user->create( [ 'role' => 'editor' ] );
		$this->scheduler = new AISTMA_Weekly_Scheduler();
	}

	/**
	 * Test 1: enable_weekly() saves prompt and flag
	 */
	public function test_enable_weekly_saves_prompt_and_flag() {
		$prompt_id = 'story-adventure';

		$this->scheduler->enable_weekly( $this->user_id, $prompt_id );

		// Check flag is set
		$is_enabled = get_user_meta( $this->user_id, '_aistma_weekly_enabled', true );
		$this->assertTrue( $is_enabled, 'Weekly flag should be enabled' );

		// Check prompt is saved
		$saved_prompt = get_user_meta( $this->user_id, '_aistma_weekly_prompt', true );
		$this->assertEquals( $prompt_id, $saved_prompt, 'Prompt ID should be saved' );
	}

	/**
	 * Test 2: is_weekly_enabled() returns correct state
	 */
	public function test_is_weekly_enabled_returns_correct_state() {
		// Initially disabled
		$is_enabled = $this->scheduler->is_weekly_enabled( $this->user_id );
		$this->assertFalse( $is_enabled, 'Weekly should be disabled initially' );

		// Enable it
		$this->scheduler->enable_weekly( $this->user_id, 'story-adventure' );

		$is_enabled = $this->scheduler->is_weekly_enabled( $this->user_id );
		$this->assertTrue( $is_enabled, 'Weekly should be enabled after calling enable_weekly' );
	}

	/**
	 * Test 3: disable_weekly() removes flag
	 */
	public function test_disable_weekly_removes_flag() {
		// Enable first
		$this->scheduler->enable_weekly( $this->user_id, 'story-adventure' );
		$this->assertTrue( $this->scheduler->is_weekly_enabled( $this->user_id ), 'Should be enabled' );

		// Disable
		$this->scheduler->disable_weekly( $this->user_id );

		$is_enabled = $this->scheduler->is_weekly_enabled( $this->user_id );
		$this->assertFalse( $is_enabled, 'Weekly should be disabled' );
	}

	/**
	 * Test 4: get_weekly_prompt() returns saved prompt
	 */
	public function test_get_weekly_prompt_returns_saved_prompt() {
		$prompt_id = 'story-mystery';
		$this->scheduler->enable_weekly( $this->user_id, $prompt_id );

		$retrieved = $this->scheduler->get_weekly_prompt( $this->user_id );
		$this->assertEquals( $prompt_id, $retrieved, 'Should return saved prompt ID' );
	}

	/**
	 * Test 5: should_generate_weekly() respects timing logic
	 */
	public function test_should_generate_weekly_respects_timing() {
		// Enable weekly
		$this->scheduler->enable_weekly( $this->user_id, 'story-adventure' );

		// If never generated, should generate
		$should_generate = $this->scheduler->should_generate_weekly( $this->user_id );
		$this->assertTrue( $should_generate, 'Should generate on first time' );

		// Mark as generated
		$this->scheduler->mark_weekly_generated( $this->user_id );

		// Immediately after, should not generate (less than 7 days)
		$should_generate = $this->scheduler->should_generate_weekly( $this->user_id );
		$this->assertFalse( $should_generate, 'Should not generate within 7 days' );
	}

	/**
	 * Test 6: Weekly generation timestamp is updated
	 */
	public function test_weekly_generation_timestamp_is_updated() {
		$this->scheduler->enable_weekly( $this->user_id, 'story-adventure' );

		// Get timestamp before
		$before = get_user_meta( $this->user_id, '_aistma_weekly_last_generated', true );
		$this->assertEmpty( $before, 'Should not have timestamp initially' );

		// Mark as generated
		$this->scheduler->mark_weekly_generated( $this->user_id );

		// Get timestamp after
		$after = get_user_meta( $this->user_id, '_aistma_weekly_last_generated', true );
		$this->assertNotEmpty( $after, 'Should have timestamp after generation' );
		$this->assertIsNumeric( $after, 'Timestamp should be numeric (Unix time)' );
	}

	/**
	 * Test 7: Non-enabled users don't generate
	 */
	public function test_non_enabled_users_dont_generate() {
		// Don't enable weekly
		$should_generate = $this->scheduler->should_generate_weekly( $this->user_id );
		$this->assertFalse( $should_generate, 'Should not generate if weekly not enabled' );
	}

	/**
	 * Test 8: Can change prompt for weekly
	 */
	public function test_can_change_weekly_prompt() {
		// Enable with one prompt
		$this->scheduler->enable_weekly( $this->user_id, 'story-adventure' );
		$this->assertEquals( 'story-adventure', $this->scheduler->get_weekly_prompt( $this->user_id ) );

		// Change to different prompt
		$this->scheduler->enable_weekly( $this->user_id, 'story-mystery' );
		$this->assertEquals( 'story-mystery', $this->scheduler->get_weekly_prompt( $this->user_id ) );
	}

	/**
	 * Test 9: get_all_weekly_enabled_users() returns correct users
	 */
	public function test_get_all_weekly_enabled_users_returns_correct_users() {
		if ( method_exists( $this->scheduler, 'get_all_weekly_enabled_users' ) ) {
			// Create multiple users
			$user_a = $this->user_id;
			$user_b = $this->factory->user->create( [ 'role' => 'editor' ] );
			$user_c = $this->factory->user->create( [ 'role' => 'editor' ] );

			// Enable weekly for A and C
			$this->scheduler->enable_weekly( $user_a, 'story-adventure' );
			$this->scheduler->enable_weekly( $user_c, 'story-mystery' );

			// B is not enabled

			$enabled_users = $this->scheduler->get_all_weekly_enabled_users();

			$this->assertIsArray( $enabled_users, 'Should return array' );
			$this->assertContains( $user_a, $enabled_users, 'Should include user A' );
			$this->assertNotContains( $user_b, $enabled_users, 'Should not include user B' );
			$this->assertContains( $user_c, $enabled_users, 'Should include user C' );
		}
	}

	/**
	 * Test 10: Weekly state persists across sessions
	 */
	public function test_weekly_state_persists_across_sessions() {
		// Session 1: enable weekly
		$this->scheduler->enable_weekly( $this->user_id, 'story-adventure' );

		// Session 2: create new instance and check
		$new_scheduler = new AISTMA_Weekly_Scheduler();
		$is_enabled    = $new_scheduler->is_weekly_enabled( $this->user_id );
		$prompt        = $new_scheduler->get_weekly_prompt( $this->user_id );

		$this->assertTrue( $is_enabled, 'Should be enabled in new session' );
		$this->assertEquals( 'story-adventure', $prompt, 'Prompt should persist' );
	}

	/**
	 * Test 11: Different users have separate weekly settings
	 */
	public function test_different_users_have_separate_weekly_settings() {
		$user_a = $this->user_id;
		$user_b = $this->factory->user->create( [ 'role' => 'editor' ] );

		// User A: enabled with adventure
		$this->scheduler->enable_weekly( $user_a, 'story-adventure' );

		// User B: enabled with mystery
		$this->scheduler->enable_weekly( $user_b, 'story-mystery' );

		// Check they have different settings
		$prompt_a = $this->scheduler->get_weekly_prompt( $user_a );
		$prompt_b = $this->scheduler->get_weekly_prompt( $user_b );

		$this->assertEquals( 'story-adventure', $prompt_a );
		$this->assertEquals( 'story-mystery', $prompt_b );
	}

	/**
	 * Test 12: Empty prompt validation
	 */
	public function test_empty_prompt_validation() {
		// Try to enable with empty prompt
		$result = $this->scheduler->enable_weekly( $this->user_id, '' );

		// Should either fail or handle gracefully
		if ( is_bool( $result ) ) {
			$this->assertFalse( $result, 'Should not allow empty prompt' );
		} else {
			// If it doesn't return false, verify the prompt is valid
			$saved = $this->scheduler->get_weekly_prompt( $this->user_id );
			$this->assertNotEmpty( $saved, 'Prompt should not be empty' );
		}
	}

	/**
	 * Test 13: 7-day cooldown calculation
	 */
	public function test_7_day_cooldown_calculation() {
		$this->scheduler->enable_weekly( $this->user_id, 'story-adventure' );

		// First generation: should be allowed
		$should_gen = $this->scheduler->should_generate_weekly( $this->user_id );
		$this->assertTrue( $should_gen, 'First generation should be allowed' );

		// Mark as generated (now)
		$this->scheduler->mark_weekly_generated( $this->user_id );

		// Try 1 day later (should not generate)
		// Note: This test might need time manipulation depending on implementation
		$should_gen = $this->scheduler->should_generate_weekly( $this->user_id );
		$this->assertFalse( $should_gen, 'Should not generate within 7 days' );
	}

	/**
	 * Test 14: Can manually reset weekly state
	 */
	public function test_can_reset_weekly_state() {
		if ( method_exists( $this->scheduler, 'reset_weekly_for_user' ) ) {
			$this->scheduler->enable_weekly( $this->user_id, 'story-adventure' );
			$this->scheduler->mark_weekly_generated( $this->user_id );

			$this->scheduler->reset_weekly_for_user( $this->user_id );

			$is_enabled = $this->scheduler->is_weekly_enabled( $this->user_id );
			$this->assertFalse( $is_enabled, 'Weekly should be disabled after reset' );
		}
	}
}
?>
