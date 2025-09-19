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
	 * Number of days to show activity for
	 */
	const ACTIVITY_DAYS = 14;

	/**
	 * Number of recent posts to display
	 */
	const RECENT_POSTS_LIMIT = 5;

	/**
	 * Initialize the widget
	 */
	public static function init() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
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
	 * Get recent posts
	 */
	private static function get_recent_posts() {
		global $wpdb;
		
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, post_title, post_date 
			FROM {$wpdb->posts} 
			WHERE post_type = 'post' 
			AND post_status = 'publish' 
			ORDER BY post_date DESC 
			LIMIT %d",
			self::RECENT_POSTS_LIMIT
		) );
	}

	/**
	 * Generate activity data for posts
	 */
	private static function generate_activity_data( $recent_posts ) {
		$post_views_by_day = array();
		$date_labels = array();
		
		// Generate date labels for the past N days
		for ( $i = self::ACTIVITY_DAYS - 1; $i >= 0; $i-- ) {
			$date_labels[] = date( 'Y-m-d', strtotime( "-{$i} days" ) );
		}
		
		// For each recent post, build simulated activity data
		foreach ( $recent_posts as $post ) {
			$post_id = (int) $post->ID;
			$post_views_by_day[ $post_id ] = array();
			
			// Generate simulated activity data (replace with real analytics)
			for ( $i = 0; $i < count( $date_labels ); $i++ ) {
				$simulated_activity = rand( 0, 25 );
				$post_views_by_day[ $post_id ][ $i ] = $simulated_activity;
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
				<p><strong><?php echo esc_html( count( $recent_posts ) ); ?></strong> <?php esc_html_e( 'recent posts activity over last 14 days', 'ai-story-maker' ); ?></p>
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
								 title="<?php echo esc_attr( sprintf( __( '%d views on %s', 'ai-story-maker' ), $activity, date_i18n( get_option( 'date_format' ), strtotime( $date_labels[ $i ] ) ) ) ); ?>">
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
			height: 30px;
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
