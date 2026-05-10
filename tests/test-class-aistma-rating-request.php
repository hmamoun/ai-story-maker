<?php
/**
 * Unit Tests for AISTMA_Rating_Request
 *
 * Tests rating request functionality:
 * - Generation count tracking
 * - Rating request at 5th generation
 * - Rate limiting (7-day cooldown)
 * - Never ask flag
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_AISTMA_Rating_Request extends WP_UnitTestCase {

	/**
	 * Holds the user ID for testing
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Rating request instance
	 *
	 * @var AISTMA_Rating_Request
	 */
	private $rating;

	/**
	 * Set up test fixtures
	 */
	public function setUp() {
		parent::setUp();
		$this->user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $this->user_id );
		$this->rating = new AISTMA_Rating_Request();
	}

	/**
	 * Test 1: Generation count increments
	 */
	public function test_generation_count_increments() {
		$initial_count = $this->rating->get_generation_count( $this->user_id );
		$this->assertEquals( 0, $initial_count, 'Initial count should be 0' );

		$this->rating->increment_generation_count( $this->user_id );
		$new_count = $this->rating->get_generation_count( $this->user_id );
		$this->assertEquals( 1, $new_count, 'Count should increment to 1' );
	}

	/**
	 * Test 2: should_show_rating() returns true at 5th generation
	 */
	public function test_should_show_rating_at_fifth_generation() {
		// Set generation count to 4 (next one will be 5th)
		update_user_meta( $this->user_id, '_aistma_generation_count', 4 );

		// Check before 5th
		$should_show = $this->rating->should_show_rating( $this->user_id );
		$this->assertFalse( $should_show, 'Should not show at 4th generation' );

		// Increment to 5th
		$this->rating->increment_generation_count( $this->user_id );

		$should_show = $this->rating->should_show_rating( $this->user_id );
		$this->assertTrue( $should_show, 'Should show at 5th generation' );
	}

	/**
	 * Test 3: Rating shows only once (7-day cooldown)
	 */
	public function test_rating_shows_only_once_with_7day_cooldown() {
		// Set generation count to 5
		update_user_meta( $this->user_id, '_aistma_generation_count', 5 );

		// First time should show
		$should_show = $this->rating->should_show_rating( $this->user_id );
		$this->assertTrue( $should_show, 'Should show on 5th generation' );

		// Record that we showed it
		$this->rating->mark_rating_shown( $this->user_id );

		// Immediately after, should not show again
		$should_show = $this->rating->should_show_rating( $this->user_id );
		$this->assertFalse( $should_show, 'Should not show again immediately after' );

		// Even if generation count reaches 10, should still not show (7-day cooldown)
		update_user_meta( $this->user_id, '_aistma_generation_count', 10 );
		$should_show = $this->rating->should_show_rating( $this->user_id );
		$this->assertFalse( $should_show, 'Should not show within 7 days (cooldown)' );
	}

	/**
	 * Test 4: Never ask flag is respected
	 */
	public function test_never_ask_flag_is_respected() {
		// Set generation count to 5 (would normally show)
		update_user_meta( $this->user_id, '_aistma_generation_count', 5 );

		// Set never ask flag
		update_user_meta( $this->user_id, '_aistma_rating_never_ask', true );

		$should_show = $this->rating->should_show_rating( $this->user_id );
		$this->assertFalse( $should_show, 'Should not show if user said "never ask"' );
	}

	/**
	 * Test 5: Rating count doesn't affect after never ask
	 */
	public function test_never_ask_persists_even_with_higher_counts() {
		// Set never ask flag
		update_user_meta( $this->user_id, '_aistma_rating_never_ask', true );

		// Try with high generation count
		update_user_meta( $this->user_id, '_aistma_generation_count', 100 );

		$should_show = $this->rating->should_show_rating( $this->user_id );
		$this->assertFalse( $should_show, 'Should never show after "never ask" is set' );
	}

	/**
	 * Test 6: Multiple generations before 5th don't trigger
	 */
	public function test_rating_only_at_5th_not_earlier() {
		for ( $i = 1; $i <= 4; $i++ ) {
			update_user_meta( $this->user_id, '_aistma_generation_count', $i );
			$should_show = $this->rating->should_show_rating( $this->user_id );
			$this->assertFalse( $should_show, "Should not show at generation $i" );
		}
	}

	/**
	 * Test 7: Can manually reset rating for testing
	 */
	public function test_can_reset_rating_state() {
		if ( method_exists( $this->rating, 'reset_rating_for_user' ) ) {
			// Set shown state
			update_user_meta( $this->user_id, '_aistma_generation_count', 10 );
			$this->rating->mark_rating_shown( $this->user_id );

			// Reset
			$this->rating->reset_rating_for_user( $this->user_id );

			// Count should be 0
			$count = $this->rating->get_generation_count( $this->user_id );
			$this->assertEquals( 0, $count, 'Count should be reset to 0' );
		}
	}

	/**
	 * Test 8: Cooldown timer works correctly
	 */
	public function test_cooldown_timer_prevents_duplicate_shows() {
		// Set to 5th generation
		update_user_meta( $this->user_id, '_aistma_generation_count', 5 );

		// Show rating
		$this->rating->mark_rating_shown( $this->user_id );

		// Get the timestamp it was shown
		$shown_time = get_user_meta( $this->user_id, '_aistma_rating_last_shown', true );
		$this->assertNotEmpty( $shown_time, 'Should record when rating was shown' );

		// Try to show again (within cooldown)
		$should_show = $this->rating->should_show_rating( $this->user_id );
		$this->assertFalse( $should_show, 'Should respect cooldown timer' );
	}

	/**
	 * Test 9: Rating modal doesn't block story creation
	 */
	public function test_rating_modal_does_not_block_story_creation() {
		// This is more of an integration test, but we can verify the flag
		update_user_meta( $this->user_id, '_aistma_generation_count', 5 );

		// Should show rating, but this shouldn't prevent further generations
		$this->rating->mark_rating_shown( $this->user_id );

		// User should still be able to generate
		$can_continue = ! $this->rating->should_block_generation( $this->user_id );
		$this->assertTrue( $can_continue, 'Rating should not block generation' );
	}

	/**
	 * Test 10: Generation count persists across sessions
	 */
	public function test_generation_count_persists_across_sessions() {
		// Session 1: increment to 3
		$this->rating->increment_generation_count( $this->user_id );
		$this->rating->increment_generation_count( $this->user_id );
		$this->rating->increment_generation_count( $this->user_id );

		// Session 2: check count
		wp_set_current_user( $this->user_id );
		$new_rating = new AISTMA_Rating_Request();
		$count      = $new_rating->get_generation_count( $this->user_id );

		$this->assertEquals( 3, $count, 'Count should persist across sessions' );
	}

	/**
	 * Test 11: Different users have separate rating states
	 */
	public function test_different_users_have_separate_rating_states() {
		$user_a = $this->user_id;
		$user_b = $this->factory->user->create( [ 'role' => 'editor' ] );

		// User A: 5 generations, rating shown
		update_user_meta( $user_a, '_aistma_generation_count', 5 );
		$this->rating->mark_rating_shown( $user_a );

		// User B: no rating shown
		update_user_meta( $user_b, '_aistma_generation_count', 5 );

		$user_a_should_show = $this->rating->should_show_rating( $user_a );
		wp_set_current_user( $user_b );
		$user_b_should_show = $this->rating->should_show_rating( $user_b );

		$this->assertFalse( $user_a_should_show, 'User A already saw rating' );
		$this->assertTrue( $user_b_should_show, 'User B should see rating' );
	}

	/**
	 * Test 12: Rating submission is recorded
	 */
	public function test_rating_submission_is_recorded() {
		if ( method_exists( $this->rating, 'submit_rating' ) ) {
			$rating_value = 4; // 4 stars

			$this->rating->submit_rating( $this->user_id, $rating_value );

			$recorded = get_user_meta( $this->user_id, '_aistma_user_rating', true );
			$this->assertEquals( $rating_value, $recorded, 'Rating should be recorded' );
		}
	}
}
?>
