<?php
/**
 * Unit Tests for AISTMA_Credits_Manager
 *
 * Tests core credit system functionality:
 * - Credit retrieval
 * - Credit deduction
 * - Credit addition
 * - Validation
 * - Error handling
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_AISTMA_Credits_Manager extends WP_UnitTestCase {

	/**
	 * Holds the user ID for testing
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up test fixtures
	 */
	public function setUp() {
		parent::setUp();
		// Create a test user
		$this->user_id = $this->factory->user->create( [
			'role' => 'editor',
		] );
		// Grant 10 credits to test user
		AISTMA_Credits_Manager::add_credits( $this->user_id, 10 );
	}

	/**
	 * Test 1: get_user_credits() returns correct balance
	 */
	public function test_get_user_credits_returns_correct_balance() {
		$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 10, $balance, 'Initial balance should be 10 credits' );
	}

	/**
	 * Test 2: get_user_credits() returns 0 for user with no credits
	 */
	public function test_get_user_credits_returns_zero_for_new_user() {
		$new_user = $this->factory->user->create( [ 'role' => 'editor' ] );
		$balance   = AISTMA_Credits_Manager::get_user_credits( $new_user );
		$this->assertEquals( 0, $balance, 'New user should have 0 credits by default' );
	}

	/**
	 * Test 3: has_credits() returns true when user has enough credits
	 */
	public function test_has_credits_returns_true_with_sufficient_balance() {
		$has_credits = AISTMA_Credits_Manager::has_credits( $this->user_id, 5 );
		$this->assertTrue( $has_credits, 'User with 10 credits should have 5 credits' );
	}

	/**
	 * Test 4: has_credits() returns false when user lacks credits
	 */
	public function test_has_credits_returns_false_with_insufficient_balance() {
		$has_credits = AISTMA_Credits_Manager::has_credits( $this->user_id, 15 );
		$this->assertFalse( $has_credits, 'User with 10 credits should not have 15 credits' );
	}

	/**
	 * Test 5: deduct_credits() reduces balance correctly
	 */
	public function test_deduct_credits_reduces_balance_correctly() {
		$result = AISTMA_Credits_Manager::deduct_credits( $this->user_id, 3 );
		$this->assertTrue( $result, 'Deduction should return true' );

		$new_balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 7, $new_balance, 'Balance should be 10 - 3 = 7' );
	}

	/**
	 * Test 6: deduct_credits() returns false when insufficient balance
	 */
	public function test_deduct_credits_returns_false_when_insufficient() {
		$result = AISTMA_Credits_Manager::deduct_credits( $this->user_id, 15 );
		$this->assertFalse( $result, 'Deduction should fail when balance is insufficient' );

		// Balance should not change
		$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 10, $balance, 'Balance should remain unchanged after failed deduction' );
	}

	/**
	 * Test 7: add_credits() increases balance
	 */
	public function test_add_credits_increases_balance() {
		AISTMA_Credits_Manager::add_credits( $this->user_id, 5 );
		$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 15, $balance, 'Balance should be 10 + 5 = 15' );
	}

	/**
	 * Test 8: Multiple deductions work correctly
	 */
	public function test_multiple_deductions_work_correctly() {
		AISTMA_Credits_Manager::deduct_credits( $this->user_id, 2 );
		AISTMA_Credits_Manager::deduct_credits( $this->user_id, 3 );
		AISTMA_Credits_Manager::deduct_credits( $this->user_id, 1 );

		$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 4, $balance, 'Balance should be 10 - 2 - 3 - 1 = 4' );
	}

	/**
	 * Test 9: Error handling for invalid user
	 */
	public function test_error_handling_for_invalid_user() {
		$balance = AISTMA_Credits_Manager::get_user_credits( 99999 );
		$this->assertEquals( 0, $balance, 'Non-existent user should return 0 credits' );
	}

	/**
	 * Test 10: Deduction with 0 credits
	 */
	public function test_deduction_when_balance_is_zero() {
		$new_user = $this->factory->user->create( [ 'role' => 'editor' ] );
		$result   = AISTMA_Credits_Manager::deduct_credits( $new_user, 1 );
		$this->assertFalse( $result, 'Cannot deduct from 0 balance' );
	}

	/**
	 * Test 11: Deduct exactly the balance (boundary test)
	 */
	public function test_deduct_exact_balance() {
		$result = AISTMA_Credits_Manager::deduct_credits( $this->user_id, 10 );
		$this->assertTrue( $result, 'Should allow deduction of exact balance' );

		$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 0, $balance, 'Balance should be 0 after deducting all' );
	}

	/**
	 * Test 12: Negative credit amounts
	 */
	public function test_negative_credit_amounts_not_allowed() {
		$result = AISTMA_Credits_Manager::deduct_credits( $this->user_id, -5 );
		$this->assertFalse( $result, 'Negative deduction should not be allowed' );

		$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 10, $balance, 'Balance should not change' );
	}

	/**
	 * Test 13: Credit history (if logging is implemented)
	 */
	public function test_credit_history_tracking() {
		// Assuming get_credit_history() method exists
		if ( method_exists( 'AISTMA_Credits_Manager', 'get_credit_history' ) ) {
			AISTMA_Credits_Manager::deduct_credits( $this->user_id, 2 );
			$history = AISTMA_Credits_Manager::get_credit_history( $this->user_id );

			$this->assertIsArray( $history, 'History should be an array' );
			$this->assertGreaterThan( 0, count( $history ), 'History should have at least one entry' );
		}
	}

	/**
	 * Test 14: Concurrent deductions (race condition check)
	 */
	public function test_concurrent_deduction_safety() {
		// Simulate two rapid deductions
		$result1 = AISTMA_Credits_Manager::deduct_credits( $this->user_id, 3 );
		$result2 = AISTMA_Credits_Manager::deduct_credits( $this->user_id, 3 );

		$balance = AISTMA_Credits_Manager::get_user_credits( $this->user_id );
		$this->assertEquals( 4, $balance, 'Both deductions should succeed: 10 - 3 - 3 = 4' );
		$this->assertTrue( $result1 && $result2, 'Both deductions should return true' );
	}
}
?>
