<?php
/**
 * Transactions Page for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker/wiki
 * @since   0.1.0
 */

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AISTMA_Transactions_Page
 *
 * Handles the transactions/history page for user credit usage.
 */
class AISTMA_Transactions_Page {

	/**
	 * Log manager instance.
	 *
	 * @var AISTMA_Log_Manager
	 */
	protected $aistma_log_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->aistma_log_manager = new AISTMA_Log_Manager();
		add_action( 'wp_ajax_aistma_clear_user_history', [ $this, 'ajax_clear_user_history' ] );
	}

	/**
	 * Render the transactions page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-story-maker' ) );
		}

		$selected_user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		$users            = get_users( [ 'fields' => [ 'ID', 'user_login', 'display_name' ] ] );
		$transactions     = [];

		if ( $selected_user_id ) {
			$transactions = AISTMA_Credits_Manager::get_credit_history( $selected_user_id );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'User Transactions', 'ai-story-maker' ); ?></h1>

			<div class="transactions-filter">
				<form method="get" action="">
					<input type="hidden" name="page" value="aistma-transactions">
					<label for="user_id"><?php esc_html_e( 'Select User:', 'ai-story-maker' ); ?></label>
					<select name="user_id" id="user_id">
						<option value=""><?php esc_html_e( '-- All Users --', 'ai-story-maker' ); ?></option>
						<?php foreach ( $users as $user ) : ?>
							<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $selected_user_id, $user->ID ); ?>>
								<?php echo esc_html( $user->display_name . ' (' . $user->user_login . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="submit" class="button" value="<?php esc_attr_e( 'View Transactions', 'ai-story-maker' ); ?>">
					<?php if ( $selected_user_id ) : ?>
						<button type="button" class="button" id="clear-history-btn" data-user-id="<?php echo esc_attr( $selected_user_id ); ?>">
							<?php esc_html_e( 'Clear History', 'ai-story-maker' ); ?>
						</button>
					<?php endif; ?>
				</form>
			</div>

			<?php if ( $selected_user_id && ! empty( $transactions ) ) : ?>
				<div class="transactions-summary">
					<h2><?php esc_html_e( 'Credit Summary', 'ai-story-maker' ); ?></h2>
					<p>
						<?php
						$current_balance = AISTMA_Credits_Manager::get_user_credits( $selected_user_id );
						printf(
							esc_html__( 'Current Balance: %d credits', 'ai-story-maker' ),
							esc_html( $current_balance )
						);
						?>
					</p>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'ai-story-maker' ); ?></th>
							<th><?php esc_html_e( 'Type', 'ai-story-maker' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'ai-story-maker' ); ?></th>
							<th><?php esc_html_e( 'Balance After', 'ai-story-maker' ); ?></th>
							<th><?php esc_html_e( 'Reason', 'ai-story-maker' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $transactions as $transaction ) : ?>
							<tr>
								<td><?php echo esc_html( $transaction['timestamp'] ); ?></td>
								<td>
									<?php
									$type_class = 'deduction' === $transaction['type'] ? 'error' : 'success';
									printf(
										'<span class="badge %s">%s</span>',
										esc_attr( $type_class ),
										esc_html( ucfirst( $transaction['type'] ) )
									);
									?>
								</td>
								<td><?php echo esc_html( $transaction['amount'] ); ?></td>
								<td><?php echo esc_html( $transaction['balance'] ); ?></td>
								<td><?php echo esc_html( $transaction['reason'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php elseif ( $selected_user_id ) : ?>
				<p><?php esc_html_e( 'No transactions found for this user.', 'ai-story-maker' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Select a user to view their transactions.', 'ai-story-maker' ); ?></p>
			<?php endif; ?>
		</div>

		<style>
			.transactions-filter {
				background: #f9f9f9;
				padding: 15px;
				margin: 20px 0;
				border: 1px solid #ddd;
				border-radius: 4px;
			}

			.transactions-filter form {
				display: flex;
				gap: 10px;
				align-items: center;
				flex-wrap: wrap;
			}

			.transactions-filter select,
			.transactions-filter input[type="submit"],
			.transactions-filter button {
				padding: 8px 12px;
				font-size: 14px;
			}

			.transactions-summary {
				background: #f0f9ff;
				padding: 15px;
				margin: 20px 0;
				border-left: 4px solid #0073aa;
			}

			.badge {
				display: inline-block;
				padding: 4px 8px;
				border-radius: 3px;
				color: #fff;
				font-size: 12px;
				font-weight: bold;
			}

			.badge.error {
				background-color: #dc3545;
			}

			.badge.success {
				background-color: #28a745;
			}
		</style>

		<script>
			(function($) {
				$(document).ready(function() {
					$('#clear-history-btn').on('click', function(e) {
						e.preventDefault();
						const userId = $(this).data('user-id');
						if (confirm('<?php esc_attr_e( 'Are you sure you want to clear the transaction history for this user? This action cannot be undone.', 'ai-story-maker' ); ?>')) {
							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'aistma_clear_user_history',
									user_id: userId,
									nonce: '<?php echo esc_js( wp_create_nonce( 'aistma_clear_history' ) ); ?>'
								},
								success: function(response) {
									if (response.success) {
										alert('<?php esc_attr_e( 'Transaction history cleared successfully.', 'ai-story-maker' ); ?>');
										location.reload();
									} else {
										alert('<?php esc_attr_e( 'Error clearing history.', 'ai-story-maker' ); ?>');
									}
								}
							});
						}
					});
				});
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Handle AJAX request to clear user transaction history.
	 *
	 * @return void
	 */
	public function ajax_clear_user_history() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized', 'ai-story-maker' ) ] );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aistma_clear_history' ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed', 'ai-story-maker' ) ] );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid user ID', 'ai-story-maker' ) ] );
		}

		AISTMA_Credits_Manager::clear_history( $user_id );
		$this->aistma_log_manager->log( 'info', 'Transaction history cleared for user ' . $user_id );

		wp_send_json_success( [ 'message' => __( 'History cleared', 'ai-story-maker' ) ] );
	}
}
