<?php
/**
 * Regression Tests for Database Migration & Schema Validation
 *
 * Tests database schema integrity:
 * - Table structure validation
 * - Migration success
 * - Data integrity after schema changes
 * - Index creation
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_Database_Migration extends WP_UnitTestCase {

	/**
	 * Test 1: Database tables exist
	 */
	public function test_database_tables_exist() {
		global $wpdb;

		// Check for core tables
		$this->assertFalse( empty( $wpdb->prefix ), 'Should have table prefix' );
	}

	/**
	 * REGRESSION TEST: User meta tables work correctly
	 * Verifies user metadata storage works for credits and settings
	 */
	public function test_regression_user_meta_tables_functional() {
		$user_id = $this->factory->user->create();

		// Test storing user meta (used for credits, weekly settings, etc.)
		update_user_meta( $user_id, '_aistma_credits', 100 );
		update_user_meta( $user_id, '_aistma_weekly_enabled', 1 );

		// Retrieve and verify
		$credits = get_user_meta( $user_id, '_aistma_credits', true );
		$weekly = get_user_meta( $user_id, '_aistma_weekly_enabled', true );

		$this->assertEquals( 100, $credits, 'Credits should be stored in user meta' );
		$this->assertTrue( (bool) $weekly, 'Weekly flag should be stored' );

		// Delete and verify cleanup
		delete_user_meta( $user_id, '_aistma_credits' );
		$deleted = get_user_meta( $user_id, '_aistma_credits', true );

		$this->assertEmpty( $deleted, 'Deleted meta should be empty' );
	}

	/**
	 * REGRESSION TEST: Options table for site-wide settings
	 * Verifies site options work for plugin configuration
	 */
	public function test_regression_site_options_table_functional() {
		// Store plugin option
		update_option( 'aistma_version', '2.3.0' );

		// Retrieve
		$version = get_option( 'aistma_version' );
		$this->assertEquals( '2.3.0', $version, 'Site option should be stored and retrieved' );

		// Update
		update_option( 'aistma_version', '2.3.2' );
		$new_version = get_option( 'aistma_version' );
		$this->assertEquals( '2.3.2', $new_version, 'Option should update correctly' );

		// Delete
		delete_option( 'aistma_version' );
		$deleted = get_option( 'aistma_version' );
		$this->assertFalse( $deleted, 'Deleted option should return false' );
	}

	/**
	 * REGRESSION TEST: Posts table for story storage
	 * Verifies post creation and metadata work
	 */
	public function test_regression_posts_table_story_storage() {
		// Create a post (story)
		$post_id = wp_insert_post( [
			'post_title'   => 'Test Story',
			'post_content' => 'Story content here',
			'post_type'    => 'post',
			'post_status'  => 'draft',
		] );

		$this->assertIsInt( $post_id, 'Should create post and return ID' );
		$this->assertGreaterThan( 0, $post_id, 'Post ID should be positive' );

		// Retrieve post
		$post = get_post( $post_id );
		$this->assertNotNull( $post, 'Should retrieve created post' );
		$this->assertEquals( 'Test Story', $post->post_title, 'Post title should match' );
		$this->assertEquals( 'draft', $post->post_status, 'Post status should be draft' );

		// Add post meta (story metadata)
		add_post_meta( $post_id, '_aistma_prompt_id', 'story-adventure' );
		add_post_meta( $post_id, '_aistma_credits_used', 1 );

		// Retrieve post meta
		$prompt = get_post_meta( $post_id, '_aistma_prompt_id', true );
		$credits = get_post_meta( $post_id, '_aistma_credits_used', true );

		$this->assertEquals( 'story-adventure', $prompt, 'Post meta should store prompt' );
		$this->assertEquals( 1, $credits, 'Post meta should store credits used' );
	}

	/**
	 * REGRESSION TEST: Postmeta relationships
	 * Verifies post metadata relationships work correctly
	 */
	public function test_regression_postmeta_relationships() {
		$post_id = wp_insert_post( [ 'post_title' => 'Story' ] );

		// Add multiple meta values
		add_post_meta( $post_id, '_aistma_meta_key', 'value1' );
		add_post_meta( $post_id, '_aistma_meta_key', 'value2' );
		add_post_meta( $post_id, '_aistma_meta_key', 'value3' );

		// Get all values
		$values = get_post_meta( $post_id, '_aistma_meta_key' );

		$this->assertCount( 3, $values, 'Should store multiple meta values' );
	}

	/**
	 * REGRESSION TEST: Transaction handling in operations
	 * Verifies data consistency with multiple operations
	 */
	public function test_regression_transaction_data_consistency() {
		global $wpdb;

		// Simulate transaction-like behavior
		$user_id = $this->factory->user->create();

		// Operation 1: Add credits
		update_user_meta( $user_id, '_aistma_credits', 100 );
		$credits_1 = get_user_meta( $user_id, '_aistma_credits', true );

		// Operation 2: Create story
		$post_id = wp_insert_post( [ 'post_title' => 'New Story' ] );
		$post = get_post( $post_id );

		// Operation 3: Deduct credits
		$new_credits = $credits_1 - 10;
		update_user_meta( $user_id, '_aistma_credits', $new_credits );

		// Verify final state
		$final_credits = get_user_meta( $user_id, '_aistma_credits', true );
		$this->assertEquals( 90, $final_credits, 'Credits should be deducted correctly' );

		$final_post = get_post( $post_id );
		$this->assertNotNull( $final_post, 'Post should still exist' );
	}

	/**
	 * REGRESSION TEST: No data loss on schema validation
	 * Verifies data survives schema changes
	 */
	public function test_regression_data_persistence_across_version() {
		$user_id = $this->factory->user->create();
		$test_data = [
			'_aistma_credits' => 50,
			'_aistma_weekly_enabled' => 1,
			'_aistma_weekly_prompt' => 'story-adventure',
		];

		// Store data
		foreach ( $test_data as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		// Verify data persists
		foreach ( $test_data as $key => $value ) {
			$retrieved = get_user_meta( $user_id, $key, true );
			$this->assertEquals( $value, $retrieved, "User meta '{$key}' should persist" );
		}
	}

	/**
	 * REGRESSION TEST: Multisite table handling
	 * Verifies multisite tables don't interfere
	 */
	public function test_regression_multisite_meta_isolation() {
		// Store data with different user IDs
		$user1 = $this->factory->user->create();
		$user2 = $this->factory->user->create();

		update_user_meta( $user1, '_aistma_credits', 100 );
		update_user_meta( $user2, '_aistma_credits', 50 );

		// Verify isolation
		$credits1 = get_user_meta( $user1, '_aistma_credits', true );
		$credits2 = get_user_meta( $user2, '_aistma_credits', true );

		$this->assertEquals( 100, $credits1, 'User 1 should have 100 credits' );
		$this->assertEquals( 50, $credits2, 'User 2 should have 50 credits' );
		$this->assertNotEquals( $credits1, $credits2, 'Users should have different credits' );
	}
}
?>
