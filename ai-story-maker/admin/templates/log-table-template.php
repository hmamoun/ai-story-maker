<?php
/**
 * Admin Log Table Template
 *
 * @package AI Story Maker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="wrap">
	<div class="aistma-style-settings">
		<h2><?php esc_html_e( 'AI Story Maker Logs', 'ai-story-maker' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'aistma_clear_logs_action', 'aistma_clear_logs_nonce' ); ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( admin_url( 'admin.php?page=aistma-settings&tab=log' ) ); ?>">
			<input type="hidden" name="action" value="aistma_clear_logs">
			<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Clear Logs', 'ai-story-maker' ); ?>">
		</form>

		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'ai-story-maker' ); ?></th>
					<th><?php esc_html_e( 'Type', 'ai-story-maker' ); ?></th>
					<th><?php esc_html_e( 'Message', 'ai-story-maker' ); ?></th>
					<th><?php esc_html_e( 'Request ID', 'ai-story-maker' ); ?></th>
					<th><?php esc_html_e( 'Timestamp', 'ai-story-maker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $logs ) ) : ?>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log->id ); ?></td>
							<td>
								<strong style="color:<?php echo $log->log_type === 'error' ? 'red' : 'green'; ?>">
									<?php echo esc_html( $log->log_type ); ?>
								</strong>
							</td>
							<td><?php echo esc_html( $log->message ); ?></td>
							<td><?php echo esc_html( $log->request_id ?: 'N/A' ); ?></td>
							<td><?php echo esc_html( $log->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5"><?php esc_html_e( 'No logs found.', 'ai-story-maker' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
