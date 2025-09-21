<?php
/**
 * Recent Posts Activity Dashboard Widget
 *
 * WordPress dashboard widget displaying recent posts activity
 * in a 14-day heatmap format showing engagement patterns.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AISTMA_Posts_Activity_Widget
 * 
 * Dashboard widget for recent posts activity visualization
 */
class AISTMA_Posts_Activity_Widget {

	/**
	 * Widget ID
	 */
	const WIDGET_ID = 'aistma_posts_activity_widget';

	/**
	 * Default number of days to show activity for
	 */
	const DEFAULT_ACTIVITY_DAYS = 14;

	/**
	 * Default number of recent posts to display
	 */
	const DEFAULT_RECENT_POSTS_LIMIT = 5;

	/**
	 * Initialize the widget
	 */
	public static function init() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_aistma_save_widget_config', array( __CLASS__, 'save_widget_config' ) );
	}

	/**
	 * Register the dashboard widget
	 */
	public static function register_widget() {
		if ( current_user_can( 'edit_posts' ) ) {
			wp_add_dashboard_widget(
				self::WIDGET_ID,
				__( 'Recent Posts Activity', 'ai-story-maker' ),
				array( __CLASS__, 'render_widget' ),
				null
			);
		}
	}

	/**
	 * Enqueue widget-specific styles and scripts
	 */
	public static function enqueue_scripts( $hook ) {
		if ( 'index.php' !== $hook ) {
			return;
		}

		wp_add_inline_style( 'dashboard', self::get_widget_styles() );
	}

	/**
	 * Get configured number of activity days
	 */
	private static function get_activity_days() {
		return (int) get_option( 'aistma_widget_activity_days', self::DEFAULT_ACTIVITY_DAYS );
	}

	/**
	 * Get configured number of recent posts
	 */
	private static function get_recent_posts_limit() {
		return (int) get_option( 'aistma_widget_recent_posts_limit', self::DEFAULT_RECENT_POSTS_LIMIT );
	}

	/**
	 * Get hide empty columns setting
	 */
	private static function get_hide_empty_columns() {
		return (bool) get_option( 'aistma_widget_hide_empty_columns', false );
	}

	/**
	 * Get recent posts
	 */
	private static function get_recent_posts() {
		global $wpdb;
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Recent posts query for activity widget
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, post_title, post_date 
			FROM {$wpdb->posts} 
			WHERE post_type = 'post' 
			AND post_status = 'publish' 
			ORDER BY post_date DESC 
			LIMIT %d",
			self::get_recent_posts_limit()
		) );
	}

	/**
	 * Generate activity data for posts
	 */
	private static function generate_activity_data( $recent_posts ) {
		global $wpdb;
		$post_views_by_day = array();
		$date_labels = array();
		$activity_days = self::get_activity_days();
		
		// Generate date labels for the past N days
		for ( $i = $activity_days - 1; $i >= 0; $i-- ) {
			$date_labels[] = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
		}
		
		// For each recent post, get actual view data from traffic table
		foreach ( $recent_posts as $post ) {
			$post_id = (int) $post->ID;
			$post_views_by_day[ $post_id ] = array();
			
			// Get actual view data from aistma_traffic_info table
			for ( $i = 0; $i < count( $date_labels ); $i++ ) {
				$date = $date_labels[ $i ];
				$next_date = gmdate( 'Y-m-d', strtotime( $date . ' +1 day' ) );
				
				// Count views for this post on this specific date
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Traffic analytics from custom table for heatmap
				$view_count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}aistma_traffic_info 
					WHERE post_id = %d 
					AND DATE(viewed_at) = %s",
					$post_id,
					$date
				) );
				
				$post_views_by_day[ $post_id ][ $i ] = (int) $view_count;
			}
		}
		
		// Filter empty columns if requested
		$hide_empty_columns = self::get_hide_empty_columns();
		if ( $hide_empty_columns && ! empty( $post_views_by_day ) && ! empty( $date_labels ) ) {
			$filtered_date_labels = array();
			$filtered_post_views_by_day = array();
			
			// Check each day to see if any post has views
			for ( $i = 0; $i < count( $date_labels ); $i++ ) {
				$has_activity = false;
				foreach ( $recent_posts as $post ) {
					$post_id = (int) $post->ID;
					if ( isset( $post_views_by_day[ $post_id ][ $i ] ) && $post_views_by_day[ $post_id ][ $i ] > 0 ) {
						$has_activity = true;
						break;
					}
				}
				
				// If this day has activity, include it
				if ( $has_activity ) {
					$new_index = count( $filtered_date_labels );
					$filtered_date_labels[] = $date_labels[ $i ];
					
					// Copy views for this day to the filtered array
					foreach ( $recent_posts as $post ) {
						$post_id = (int) $post->ID;
						if ( ! isset( $filtered_post_views_by_day[ $post_id ] ) ) {
							$filtered_post_views_by_day[ $post_id ] = array();
						}
						$filtered_post_views_by_day[ $post_id ][ $new_index ] = isset( $post_views_by_day[ $post_id ][ $i ] ) ? $post_views_by_day[ $post_id ][ $i ] : 0;
					}
				}
			}
			
			// Use filtered data if we have any days with activity
			if ( ! empty( $filtered_date_labels ) ) {
				$date_labels = $filtered_date_labels;
				$post_views_by_day = $filtered_post_views_by_day;
			}
		}
		
		return array(
			'activity_data' => $post_views_by_day,
			'date_labels' => $date_labels
		);
	}

	/**
	 * Render the dashboard widget
	 */
	public static function render_widget() {
		$recent_posts = self::get_recent_posts();
		
		if ( empty( $recent_posts ) ) {
			echo '<p>' . esc_html__( 'No recent posts found.', 'ai-story-maker' ) . '</p>';
			return;
		}

		$activity_data = self::generate_activity_data( $recent_posts );
		$post_views_by_day = $activity_data['activity_data'];
		$date_labels = $activity_data['date_labels'];
		?>
		<div class="aistma-posts-activity-widget">
			<div class="aistma-widget-summary">
				<p><strong><?php echo esc_html( count( $recent_posts ) ); ?></strong> <?php 
				/* translators: %d: number of days shown in the activity widget */
				echo esc_html( sprintf( __( 'recent posts activity over last %d days', 'ai-story-maker' ), self::get_activity_days() ) ); ?></p>
			</div>

			<div class="aistma-activity-heatmap">
				<div class="aistma-heatmap-container-horizontal">
					<!-- Column headers: dates -->
					<div class="aistma-heatmap-dates" style="grid-template-columns: 180px repeat(<?php echo count( $date_labels ); ?>, 14px);">
						<div class="post-label">&nbsp;</div>
						<?php foreach ( $date_labels as $date_label ) : ?>
							<div class="date-vertical"><?php echo esc_html( date_i18n( 'M j', strtotime( $date_label ) ) ); ?></div>
						<?php endforeach; ?>
					</div>
					
					<!-- Rows: each recent post -->
					<?php foreach ( $recent_posts as $post ) : 
						$post_id = (int) $post->ID; 
					?>
						<div class="aistma-heatmap-row" style="grid-template-columns: 180px repeat(<?php echo count( $date_labels ); ?>, 14px);">
							<div class="post-label">
								<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" title="<?php echo esc_attr( $post->post_title ); ?>">
									<?php echo esc_html( wp_html_excerpt( $post->post_title ?: __( '(no title)', 'ai-story-maker' ), 25, '…' ) ); ?>
								</a>
							</div>
							<?php for ( $i = 0; $i < count( $date_labels ); $i++ ) :
								$activity = isset( $post_views_by_day[ $post_id ][ $i ] ) ? (int) $post_views_by_day[ $post_id ][ $i ] : 0;
								$intensity = 'intensity-0';
								if ( $activity > 0 ) {
									if ( $activity <= 5 ) {
										$intensity = 'intensity-1';
									} elseif ( $activity <= 10 ) {
										$intensity = 'intensity-2';
									} elseif ( $activity <= 20 ) {
										$intensity = 'intensity-3';
									} else {
										$intensity = 'intensity-4';
									}
								}
							?>
						<div class="aistma-heatmap-day <?php echo esc_attr( $intensity ); ?>" 
							 title="<?php 
							 /* translators: 1: number of views, 2: formatted date */
							 echo esc_attr( sprintf( __( '%1$d views on %2$s', 'ai-story-maker' ), $activity, date_i18n( get_option( 'date_format' ), strtotime( $date_labels[ $i ] ) ) ) ); ?>">
							</div>
							<?php endfor; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="aistma-widget-footer">
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aistma-settings&tab=analytics' ) ); ?>" class="button button-primary button-small">
					<?php esc_html_e( 'View Analytics', 'ai-story-maker' ); ?>
				</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}



	/**
	 * Save widget configuration via AJAX
	 */
	public static function save_widget_config() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aistma_widget_config' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check user capabilities
		if ( ! current_user_can( 'edit_dashboard' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		// Sanitize and validate input
		$activity_days = isset( $_POST['activity_days'] ) ? (int) $_POST['activity_days'] : 14;
		$recent_posts_limit = isset( $_POST['recent_posts_limit'] ) ? (int) $_POST['recent_posts_limit'] : 5;
		$hide_empty_columns = isset( $_POST['hide_empty_columns'] ) ? (bool) $_POST['hide_empty_columns'] : false;

		// Validate ranges
		if ( $activity_days < 1 || $activity_days > 90 ) {
			wp_send_json_error( 'Invalid activity days range' );
		}

		if ( $recent_posts_limit < 1 || $recent_posts_limit > 20 ) {
			wp_send_json_error( 'Invalid recent posts limit range' );
		}

		// Save options
		update_option( 'aistma_widget_activity_days', $activity_days );
		update_option( 'aistma_widget_recent_posts_limit', $recent_posts_limit );
		update_option( 'aistma_widget_hide_empty_columns', $hide_empty_columns );

		wp_send_json_success( 'Settings saved successfully' );
	}

	/**
	 * Get widget-specific CSS styles
	 */
	private static function get_widget_styles() {
		return '
		.aistma-posts-activity-widget {
			font-size: 12px;
		}
		.aistma-posts-activity-widget .aistma-widget-summary {
			margin-bottom: 15px;
			padding: 10px;
			background: #f8f9fa;
			border-radius: 4px;
			text-align: center;
		}
		.aistma-posts-activity-widget .aistma-activity-heatmap {
			max-height: 200px;
			overflow-y: auto;
		}
		.aistma-posts-activity-widget .aistma-heatmap-container-horizontal {
			display: flex;
			flex-direction: column;
			gap: 2px;
		}
		.aistma-posts-activity-widget .aistma-heatmap-dates,
		.aistma-posts-activity-widget .aistma-heatmap-row {
			display: grid;
			align-items: center;
			gap: 2px;
		}
		.aistma-posts-activity-widget .aistma-heatmap-dates .date-vertical {
			width: 14px;
			font-size: 9px;
			color: #666;
			writing-mode: vertical-rl;
			transform: rotate(180deg);
			text-align: left;
			height: 60px;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.aistma-posts-activity-widget .aistma-heatmap-row .aistma-heatmap-day {
			width: 12px;
			height: 12px;
			border-radius: 2px;
			cursor: pointer;
		}
		.aistma-posts-activity-widget .aistma-heatmap-row .post-label {
			width: 180px;
			text-align: left;
			font-size: 11px;
			color: #333;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			line-height: 1.2;
		}
		.aistma-posts-activity-widget .aistma-heatmap-row .post-label a {
			text-decoration: none;
			color: #0073aa;
		}
		.aistma-posts-activity-widget .aistma-heatmap-row .post-label a:hover {
			text-decoration: underline;
		}
		.aistma-posts-activity-widget .aistma-heatmap-dates .post-label {
			width: 180px;
			font-weight: bold;
			color: #666;
		}
		.aistma-posts-activity-widget .intensity-0 { background-color: #ebedf0; }
		.aistma-posts-activity-widget .intensity-1 { background-color: #c6e48b; }
		.aistma-posts-activity-widget .intensity-2 { background-color: #7bc96f; }
		.aistma-posts-activity-widget .intensity-3 { background-color: #239a3b; }
		.aistma-posts-activity-widget .intensity-4 { background-color: #196127; }
		.aistma-posts-activity-widget .aistma-widget-footer {
			margin-top: 15px;
			text-align: center;
			border-top: 1px solid #eee;
			padding-top: 10px;
		}
		.aistma-posts-activity-widget .aistma-widget-footer .button {
			margin: 0 2px;
		}
		';
	}
}

// Initialize the widget
AISTMA_Posts_Activity_Widget::init();
