<?php
/**
 * Weekly Management Template
 *
 * Displays weekly auto-generation users and management options for admins.
 *
 * @package AI_Story_Maker
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use exedotcom\aistorymaker\AISTMA_Weekly_Scheduler;

// Only show for admins
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$weekly_users = AISTMA_Weekly_Scheduler::get_weekly_enabled_users();
?>

<div class="aistma-weekly-management">
	<h3><?php esc_html_e( 'Weekly Auto-Generation Management', 'ai-story-maker' ); ?></h3>
	
	<p><?php esc_html_e( 'Manage users who have enabled weekly auto-generation of stories.', 'ai-story-maker' ); ?></p>

	<?php
	$weekly_nonce = wp_create_nonce( 'aistma_weekly_nonce' );
	?>
	<script type="text/javascript">
		(function($) {
			$(document).ready(function() {
				$('.aistma-weekly-toggle-user').on('click', function(e) {
					e.preventDefault();
					var $button = $(this);
					var userId = $button.data('user-id');
					var action = $button.data('action');

					if (!confirm('<?php esc_js_e( 'Are you sure?', 'ai-story-maker' ); ?>')) {
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'aistma_toggle_weekly_user',
							nonce: '<?php echo esc_js( $weekly_nonce ); ?>',
							user_id: userId,
							action_type: action
						},
						success: function(response) {
							if (response.success) {
								alert(response.data.message || '<?php esc_js_e( 'Action completed.', 'ai-story-maker' ); ?>');
								location.reload();
							} else {
								alert(response.data.message || '<?php esc_js_e( 'An error occurred.', 'ai-story-maker' ); ?>');
							}
						},
						error: function() {
							alert('<?php esc_js_e( 'An error occurred. Please try again.', 'ai-story-maker' ); ?>');
						}
					});
				});
			});
		})(jQuery);
	</script>

	<?php if ( empty( $weekly_users ) ) : ?>
		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'No users have weekly auto-generation enabled yet.', 'ai-story-maker' ); ?></p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'User', 'ai-story-maker' ); ?></th>
					<th><?php esc_html_e( 'Prompt', 'ai-story-maker' ); ?></th>
					<th><?php esc_html_e( 'Last Generated', 'ai-story-maker' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'ai-story-maker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $weekly_users as $user_id ) : ?>
					<?php
					$user = get_user_by( 'id', $user_id );
					$prompt_id = AISTMA_Weekly_Scheduler::get_weekly_prompt( $user_id );
					$last_generated = get_user_meta( $user_id, AISTMA_Weekly_Scheduler::META_KEY_WEEKLY_LAST_GENERATED, true );
					
					if ( $prompt_id ) {
						$prompt = get_post( $prompt_id );
						$prompt_name = $prompt ? $prompt->post_title : __( 'Unknown Prompt', 'ai-story-maker' );
					} else {
						$prompt_name = __( 'Not Set', 'ai-story-maker' );
					}
					
					if ( $last_generated ) {
						$formatted_date = wp_date( 'Y-m-d H:i', (int) $last_generated );
					} else {
						$formatted_date = __( 'Never', 'ai-story-maker' );
					}
					?>
					<tr>
						<td>
							<?php if ( $user ) : ?>
								<strong><?php echo esc_html( $user->display_name ); ?></strong>
								<br><small><?php echo esc_html( $user->user_email ); ?></small>
							<?php else : ?>
								<em><?php esc_html_e( 'User Deleted', 'ai-story-maker' ); ?></em>
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html( $prompt_name ); ?>
							<?php if ( $prompt_id ) : ?>
								<br><small>#<?php echo esc_html( $prompt_id ); ?></small>
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html( $formatted_date ); ?>
						</td>
						<td>
							<button type="button" class="button button-small aistma-weekly-toggle-user" data-user-id="<?php echo esc_attr( $user_id ); ?>" data-action="disable">
								<?php esc_html_e( 'Disable', 'ai-story-maker' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="description">
			<?php printf(
				/* translators: %d: number of users */
				esc_html( _n( 'Currently %d user has weekly auto-generation enabled.', 'Currently %d users have weekly auto-generation enabled.', count( $weekly_users ), 'ai-story-maker' ) ),
				count( $weekly_users )
			); ?>
		</p>
	<?php endif; ?>

	<div class="aistma-weekly-info-box" style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #0073aa; border-radius: 3px;">
		<p><strong><?php esc_html_e( 'How Weekly Auto-Generation Works:', 'ai-story-maker' ); ?></strong></p>
		<ul style="margin: 10px 0 0 20px;">
			<li><?php esc_html_e( 'Users can enable weekly auto-generation after generating a story in the wizard.', 'ai-story-maker' ); ?></li>
			<li><?php esc_html_e( 'The system automatically generates a story every 7 days using the saved prompt.', 'ai-story-maker' ); ?></li>
			<li><?php esc_html_e( 'Each weekly generation uses 1 credit. Users need available credits for generation to succeed.', 'ai-story-maker' ); ?></li>
			<li><?php esc_html_e( 'Weekly generation runs during regular WordPress cron events.', 'ai-story-maker' ); ?></li>
		</ul>
	</div>
</div>

<style>
	.aistma-weekly-management {
		margin-top: 30px;
		padding: 20px;
		background: #f9f9f9;
		border: 1px solid #ddd;
		border-radius: 5px;
	}

	.aistma-weekly-management h3 {
		margin-top: 0;
		color: #1d2327;
	}

	.aistma-weekly-management table {
		margin: 20px 0;
	}

	.aistma-weekly-management .button-small {
		padding: 4px 8px;
		font-size: 12px;
		height: auto;
	}

	.aistma-weekly-management .notice {
		margin: 15px 0;
	}
</style>
