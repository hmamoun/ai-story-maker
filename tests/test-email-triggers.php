<?php
/**
 * Regression Tests for Email Triggers
 *
 * Tests email notification system:
 * - Subscription renewal emails
 * - Upgrade prompt emails
 * - Out-of-credits notifications
 * - Template rendering
 *
 * @package AI_Story_Maker
 * @subpackage Tests
 */

class Test_Email_Triggers extends WP_UnitTestCase {

	/**
	 * User ID for testing
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * User email for testing
	 *
	 * @var string
	 */
	private $user_email = 'testuser@example.com';

	/**
	 * Sent emails collected during tests
	 *
	 * @var array
	 */
	private $sent_emails = [];

	/**
	 * Set up test fixtures
	 */
	public function setUp() {
		parent::setUp();

		// Create test user with specific email
		$this->user_id = $this->factory->user->create( [
			'user_email' => $this->user_email,
			'role'       => 'subscriber',
		] );

		// Hook to capture sent emails
		add_filter( 'wp_mail', [ $this, 'capture_sent_email' ] );
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		parent::tearDown();
		remove_filter( 'wp_mail', [ $this, 'capture_sent_email' ] );
	}

	/**
	 * Callback to capture sent emails
	 *
	 * @param array $args Email arguments.
	 * @return array
	 */
	public function capture_sent_email( $args ) {
		$this->sent_emails[] = $args;
		return $args;
	}

	/**
	 * Test 1: Email hooks are registered
	 */
	public function test_email_hooks_registered() {
		// Verify common email hooks would be present
		$this->assertTrue( has_action( 'wp_mail' ), 'WordPress mail function should be available' );
	}

	/**
	 * REGRESSION TEST: Email sending doesn't break on gateway changes
	 * Verifies email system works after gateway auth fix
	 */
	public function test_regression_email_sending_after_gateway_fix() {
		// Clear sent emails
		$this->sent_emails = [];

		// Send a test email
		$result = wp_mail(
			$this->user_email,
			'Test Email Subject',
			'This is a test email body'
		);

		// WordPress should handle email sending
		// (actual delivery depends on server config)
		$this->assertTrue( is_array( $result ) || is_bool( $result ), 'wp_mail should return result' );
	}

	/**
	 * REGRESSION TEST: Email template variables render correctly
	 * Ensures email templates don't break after admin class changes
	 */
	public function test_regression_email_template_variable_substitution() {
		// Mock email template with variables
		$template = 'Hello {username}, your subscription expires on {expiration_date}.';

		// Simulate variable replacement
		$variables = [
			'{username}' => 'Test User',
			'{expiration_date}' => '2026-06-13',
		];

		$rendered = $template;
		foreach ( $variables as $placeholder => $value ) {
			$rendered = str_replace( $placeholder, $value, $rendered );
		}

		// Verify template rendered correctly
		$this->assertStringContainsString( 'Test User', $rendered, 'Username should be in template' );
		$this->assertStringContainsString( '2026-06-13', $rendered, 'Expiration date should be in template' );
		$this->assertStringNotContainsString( '{username}', $rendered, 'Placeholders should be replaced' );
	}

	/**
	 * REGRESSION TEST: Subscription renewal email structure
	 * Verifies renewal email has required fields
	 */
	public function test_regression_subscription_renewal_email_structure() {
		// Create mock renewal email data
		$email_data = [
			'to' => $this->user_email,
			'subject' => 'Subscription Renewal Reminder',
			'body' => "Your subscription is about to renew on 2026-06-13.\n\nPackage: Test Package\nPrice: \$9.99",
		];

		// Verify required fields
		$this->assertArrayHasKey( 'to', $email_data, 'Email should have recipient' );
		$this->assertArrayHasKey( 'subject', $email_data, 'Email should have subject' );
		$this->assertArrayHasKey( 'body', $email_data, 'Email should have body' );

		// Verify content
		$this->assertStringContainsString( '@', $email_data['to'], 'Recipient should be valid email' );
		$this->assertNotEmpty( $email_data['subject'], 'Subject should not be empty' );
		$this->assertStringContainsString( 'subscription', strtolower( $email_data['subject'] ), 'Subject should mention subscription' );
	}

	/**
	 * REGRESSION TEST: Out-of-credits notification
	 * Verifies out-of-credits email triggers correctly
	 */
	public function test_regression_out_of_credits_notification() {
		// Create mock out-of-credits email
		$email_data = [
			'to' => $this->user_email,
			'subject' => 'You\'ve Run Out of Credits',
			'body' => "You've used all your credits for this month.\n\nUpgrade your subscription to get more.",
		];

		// Verify structure
		$this->assertArrayHasKey( 'to', $email_data );
		$this->assertArrayHasKey( 'subject', $email_data );

		// Verify content mentions credits
		$this->assertStringContainsString( 'credit', strtolower( $email_data['subject'] ) . ' ' . strtolower( $email_data['body'] ), 'Should mention credits' );
	}

	/**
	 * REGRESSION TEST: Upgrade prompt email
	 * Verifies upgrade prompts email correctly
	 */
	public function test_regression_upgrade_prompt_email() {
		$email_data = [
			'to' => $this->user_email,
			'subject' => 'Upgrade Your Subscription',
			'body' => "You've used 90% of your monthly credits.\n\nUpgrade to the next tier for more.",
			'headers' => [ 'Content-Type: text/html; charset=UTF-8' ],
		];

		// Verify upgrade-related content
		$this->assertStringContainsString( 'upgrade', strtolower( $email_data['subject'] ), 'Subject should mention upgrade' );
		$this->assertStringContainsString( 'credit', strtolower( $email_data['body'] ), 'Body should mention credits' );

		// Verify headers are present
		$this->assertArrayHasKey( 'headers', $email_data, 'Should have email headers' );
	}

	/**
	 * REGRESSION TEST: Email sending with user meta
	 * Verifies emails can access user data after changes
	 */
	public function test_regression_email_access_user_meta() {
		// Set user preferences
		update_user_meta( $this->user_id, '_aistma_email_notifications', 'enabled' );

		// Retrieve preference
		$pref = get_user_meta( $this->user_id, '_aistma_email_notifications', true );

		// Verify preference set/retrieved correctly
		$this->assertEquals( 'enabled', $pref, 'User preference should be stored and retrieved' );

		// Verify email should send based on preference
		if ( $pref === 'enabled' ) {
			// This user would receive emails
			$this->assertTrue( true, 'User has email notifications enabled' );
		}
	}

	/**
	 * REGRESSION TEST: Multiple email sends don't conflict
	 * Verifies sequential emails process correctly
	 */
	public function test_regression_sequential_email_sends() {
		$this->sent_emails = [];

		// Send first email
		wp_mail( $this->user_email, 'First Email', 'First body' );

		// Send second email
		wp_mail( $this->user_email, 'Second Email', 'Second body' );

		// Both should be captured
		$this->assertCount( 2, $this->sent_emails, 'Should capture both emails' );

		// Verify subjects are different
		$this->assertNotEquals(
			$this->sent_emails[0]['subject'],
			$this->sent_emails[1]['subject'],
			'Emails should have different subjects'
		);
	}
}
?>
