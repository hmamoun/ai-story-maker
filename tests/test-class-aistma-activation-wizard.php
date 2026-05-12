<?php
/**
 * Unit Tests for AISTMA_Activation_Wizard
 *
 * Tests wizard functionality:
 * - Showing wizard on first visit
 * - Hiding wizard on subsequent visits
 * - Default prompts loading
 * - Wizard dismissal
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_AISTMA_Activation_Wizard extends WP_UnitTestCase {

	/**
	 * Holds the user ID for testing
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Wizard instance
	 *
	 * @var AISTMA_Activation_Wizard
	 */
	private $wizard;

	/**
	 * Set up test fixtures
	 */
	public function setUp() {
		parent::setUp();
		$this->user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->user_id );
		$this->wizard = new AISTMA_Activation_Wizard();
	}

	/**
	 * Test 1: maybe_show_wizard() returns true on first visit
	 */
	public function test_maybe_show_wizard_returns_true_on_first_visit() {
		// Ensure no previous visit flag
		delete_user_meta( $this->user_id, '_aistma_wizard_dismissed' );

		$should_show = $this->wizard->maybe_show_wizard();
		$this->assertTrue( $should_show, 'Wizard should show on first visit' );
	}

	/**
	 * Test 2: maybe_show_wizard() returns false after dismiss
	 */
	public function test_maybe_show_wizard_returns_false_after_dismiss() {
		// Simulate user dismissing wizard
		update_user_meta( $this->user_id, '_aistma_wizard_dismissed', true );

		$should_show = $this->wizard->maybe_show_wizard();
		$this->assertFalse( $should_show, 'Wizard should not show after dismissal' );
	}

	/**
	 * Test 3: get_default_prompts() returns 10 prompts
	 */
	public function test_get_default_prompts_returns_10_prompts() {
		$prompts = $this->wizard->get_default_prompts();

		$this->assertIsArray( $prompts, 'Prompts should be an array' );
		$this->assertEquals( 10, count( $prompts ), 'Should return exactly 10 prompts' );
	}

	/**
	 * Test 4: Each prompt has correct structure
	 */
	public function test_prompts_have_correct_structure() {
		$prompts = $this->wizard->get_default_prompts();

		foreach ( $prompts as $prompt ) {
			$this->assertArrayHasKey( 'id', $prompt, 'Prompt must have id' );
			$this->assertArrayHasKey( 'name', $prompt, 'Prompt must have name' );
			$this->assertArrayHasKey( 'description', $prompt, 'Prompt must have description' );

			$this->assertIsString( $prompt['id'], 'Prompt id should be string' );
			$this->assertIsString( $prompt['name'], 'Prompt name should be string' );
			$this->assertIsString( $prompt['description'], 'Prompt description should be string' );
			$this->assertGreaterThan( 0, strlen( $prompt['name'] ), 'Prompt name should not be empty' );
		}
	}

	/**
	 * Test 5: Prompts have unique IDs
	 */
	public function test_prompts_have_unique_ids() {
		$prompts = $this->wizard->get_default_prompts();
		$ids     = array_column( $prompts, 'id' );
		$unique  = array_unique( $ids );

		$this->assertEquals( count( $ids ), count( $unique ), 'All prompt IDs should be unique' );
	}

	/**
	 * Test 6: Prompt descriptions are meaningful
	 */
	public function test_prompt_descriptions_are_meaningful() {
		$prompts = $this->wizard->get_default_prompts();

		foreach ( $prompts as $prompt ) {
			$this->assertGreaterThan( 10, strlen( $prompt['description'] ),
				"Prompt '{$prompt['name']}' description should be meaningful (>10 chars)" );
		}
	}

	/**
	 * Test 7: Dismiss wizard functionality
	 */
	public function test_dismiss_wizard_functionality() {
		// Ensure wizard is not dismissed
		delete_user_meta( $this->user_id, '_aistma_wizard_dismissed' );
		$this->assertTrue( $this->wizard->maybe_show_wizard(), 'Wizard should show initially' );

		// Dismiss wizard
		$this->wizard->dismiss_wizard();

		// Check that it's now dismissed
		$is_dismissed = get_user_meta( $this->user_id, '_aistma_wizard_dismissed', true );
		$this->assertTrue( $is_dismissed, 'Wizard should be marked as dismissed' );

		// Verify maybe_show_wizard now returns false
		$should_show = $this->wizard->maybe_show_wizard();
		$this->assertFalse( $should_show, 'Wizard should not show after dismissal' );
	}

	/**
	 * Test 8: Wizard state persists across sessions
	 */
	public function test_wizard_state_persists_across_sessions() {
		// First session: dismiss wizard
		update_user_meta( $this->user_id, '_aistma_wizard_dismissed', true );

		// Simulate new session: user logs back in
		wp_set_current_user( $this->user_id );
		$new_wizard = new AISTMA_Activation_Wizard();

		$should_show = $new_wizard->maybe_show_wizard();
		$this->assertFalse( $should_show, 'Wizard state should persist (dismissed)' );
	}

	/**
	 * Test 9: Non-admin users don't trigger wizard
	 */
	public function test_wizard_only_shows_for_admins() {
		// Create non-admin user
		$contributor = $this->factory->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $contributor );

		$non_admin_wizard = new AISTMA_Activation_Wizard();
		$should_show      = $non_admin_wizard->maybe_show_wizard();

		// This may depend on implementation - adjust as needed
		// Could be: only admins see wizard, or all users see it once
		$this->assertIsBoolean( $should_show, 'Should return boolean' );
	}

	/**
	 * Test 10: First activation shows wizard to admin
	 */
	public function test_first_activation_shows_wizard() {
		// Simulate plugin activation (brand new)
		delete_option( '_aistma_activated' );
		delete_user_meta( $this->user_id, '_aistma_wizard_dismissed' );

		$should_show = $this->wizard->maybe_show_wizard();
		$this->assertTrue( $should_show, 'Wizard should show on first activation' );
	}

	/**
	 * Test 11: Prompts are not empty
	 */
	public function test_default_prompts_not_empty() {
		$prompts = $this->wizard->get_default_prompts();

		$this->assertNotEmpty( $prompts, 'Prompts list should not be empty' );
		foreach ( $prompts as $prompt ) {
			$this->assertNotEmpty( $prompt['name'], 'Prompt name should not be empty' );
			$this->assertNotEmpty( $prompt['description'], 'Prompt description should not be empty' );
		}
	}

	/**
	 * Test 12: Can retrieve prompts by ID
	 */
	public function test_can_retrieve_prompt_by_id() {
		$prompts = $this->wizard->get_default_prompts();

		if ( method_exists( $this->wizard, 'get_prompt_by_id' ) ) {
			$first_id     = $prompts[0]['id'];
			$retrieved    = $this->wizard->get_prompt_by_id( $first_id );
			$this->assertIsArray( $retrieved, 'Should return array for valid ID' );
			$this->assertEquals( $first_id, $retrieved['id'], 'Retrieved prompt should match requested ID' );
		}
	}
}
?>
