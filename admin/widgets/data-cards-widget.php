<?php
/**
 * Data Cards Dashboard Widget
 *
 * WordPress dashboard widget displaying key statistics and metrics
 * in an easy-to-read card format showing AI Story Maker performance.
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
 * Class AISTMA_Data_Cards_Widget
 * 
 * Dashboard widget for key statistics and data visualization
 */
class AISTMA_Data_Cards_Widget {

	/**
	 * Widget ID
	 */
	const WIDGET_ID = 'aistma_data_cards_widget';

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
				__( 'AI Story Maker - Data Overview', 'ai-story-maker' ),
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
		wp_add_inline_script( 'dashboard', self::get_widget_scripts() );
	}

	/**
	 * Get comprehensive statistics
	 */
	private static function get_statistics() {
		global $wpdb;
		
		$stats = array();
		
		// Total published posts
		$stats['total_posts'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
			WHERE post_type = 'post' AND post_status = 'publish'"
		);
		
		// AI-generated posts
		$stats['ai_generated_posts'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} p 
			JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
			WHERE p.post_type = 'post' AND p.post_status = 'publish' 
			AND pm.meta_key = '_aistma_generated'"
		);
		
		// Posts this week
		$stats['posts_this_week'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
			WHERE post_type = 'post' AND post_status = 'publish' 
			AND post_date >= %s",
			date( 'Y-m-d', strtotime( '-7 days' ) )
		) );
		
		// Posts this month
		$stats['posts_this_month'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
			WHERE post_type = 'post' AND post_status = 'publish' 
			AND post_date >= %s",
			date( 'Y-m-01' )
		) );
		
		// AI stories in last 6 months
		$six_months_ago = date( 'Y-m-d', strtotime( '-180 days' ) );
		$story_results = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(post_date) as date, COUNT(*) as count 
			FROM {$wpdb->posts} 
			WHERE post_type = 'post' AND post_status = 'publish' 
			AND post_date >= %s
			AND ID IN (
				SELECT post_id FROM {$wpdb->postmeta} 
				WHERE meta_key = '_aistma_generated'
			)
			GROUP BY DATE(post_date)",
			$six_months_ago
		) );
		
		$story_data = array();
		foreach ( $story_results as $row ) {
			$story_data[ $row->date ] = (int) $row->count;
		}
		
		$stats['total_stories_6months'] = array_sum( $story_data );
		$stats['max_stories_per_day'] = ! empty( $story_data ) ? max( $story_data ) : 0;
		
		// Calculate AI content percentage
		$stats['ai_percentage'] = $stats['total_posts'] > 0 
			? round( ( $stats['ai_generated_posts'] / $stats['total_posts'] ) * 100, 1 )
			: 0;
		
		return $stats;
	}

	/**
	 * Get recent posts for quick reference
	 */
	private static function get_recent_posts() {
		global $wpdb;
		
		return $wpdb->get_results(
			"SELECT ID, post_title, post_date 
			FROM {$wpdb->posts} 
			WHERE post_type = 'post' AND post_status = 'publish' 
			ORDER BY post_date DESC 
			LIMIT 3"
		);
	}

	/**
	 * Render the dashboard widget
	 */
	public static function render_widget() {
		$stats = self::get_statistics();
		$recent_posts = self::get_recent_posts();
		?>
		<div class="aistma-data-cards-widget">
			<!-- Primary Stats Cards -->
			<div class="aistma-data-cards-grid">
				<div class="aistma-data-card primary">
					<div class="data-card-number"><?php echo esc_html( $stats['total_posts'] ); ?></div>
					<div class="data-card-label"><?php esc_html_e( 'Total Posts', 'ai-story-maker' ); ?></div>
					<div class="data-card-trend">
						<span class="trend-indicator">📊</span>
						<?php printf( esc_html__( '%d this week', 'ai-story-maker' ), $stats['posts_this_week'] ); ?>
					</div>
				</div>

				<div class="aistma-data-card success">
					<div class="data-card-number"><?php echo esc_html( $stats['ai_generated_posts'] ); ?></div>
					<div class="data-card-label"><?php esc_html_e( 'AI Generated', 'ai-story-maker' ); ?></div>
					<div class="data-card-trend">
						<span class="trend-indicator">🤖</span>
						<?php printf( esc_html__( '%s%% of total', 'ai-story-maker' ), $stats['ai_percentage'] ); ?>
					</div>
				</div>

				<div class="aistma-data-card info">
					<div class="data-card-number"><?php echo esc_html( $stats['total_stories_6months'] ); ?></div>
					<div class="data-card-label"><?php esc_html_e( 'Stories (6 months)', 'ai-story-maker' ); ?></div>
					<div class="data-card-trend">
						<span class="trend-indicator">📅</span>
						<?php printf( esc_html__( 'Max %d/day', 'ai-story-maker' ), $stats['max_stories_per_day'] ); ?>
					</div>
				</div>

				<div class="aistma-data-card warning">
					<div class="data-card-number"><?php echo esc_html( $stats['posts_this_month'] ); ?></div>
					<div class="data-card-label"><?php esc_html_e( 'This Month', 'ai-story-maker' ); ?></div>
					<div class="data-card-trend">
						<span class="trend-indicator">📈</span>
						<?php 
						$daily_avg = $stats['posts_this_month'] > 0 ? round( $stats['posts_this_month'] / date( 'j' ), 1 ) : 0;
						printf( esc_html__( '%s per day', 'ai-story-maker' ), $daily_avg );
						?>
					</div>
				</div>
			</div>

			<!-- Recent Posts Quick List -->
			<?php if ( ! empty( $recent_posts ) ) : ?>
			<div class="aistma-recent-posts-quick">
				<h4><?php esc_html_e( 'Latest Posts', 'ai-story-maker' ); ?></h4>
				<ul class="recent-posts-list">
					<?php foreach ( $recent_posts as $post ) : ?>
					<li class="recent-post-item">
						<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank" class="post-title">
							<?php echo esc_html( wp_html_excerpt( $post->post_title ?: __( '(no title)', 'ai-story-maker' ), 40, '…' ) ); ?>
						</a>
						<span class="post-date">
							<?php echo esc_html( human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ) ); ?> 
							<?php esc_html_e( 'ago', 'ai-story-maker' ); ?>
						</span>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<!-- Quick Actions -->
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
		.aistma-data-cards-widget {
			font-size: 13px;
		}
		.aistma-data-cards-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
			gap: 10px;
			margin-bottom: 20px;
		}
		.aistma-data-card {
			background: #ffffff;
			border: 1px solid #e6e6e6;
			border-radius: 8px;
			padding: 12px;
			text-align: center;
			position: relative;
			transition: all 0.2s ease;
		}
		.aistma-data-card:hover {
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			transform: translateY(-2px);
		}
		.aistma-data-card.primary {
			border-left: 4px solid #0073aa;
		}
		.aistma-data-card.success {
			border-left: 4px solid #46b450;
		}
		.aistma-data-card.info {
			border-left: 4px solid #00a0d2;
		}
		.aistma-data-card.warning {
			border-left: 4px solid #f56e28;
		}
		.aistma-data-card .data-card-number {
			font-size: 20px;
			font-weight: 700;
			color: #2c3e50;
			line-height: 1;
		}
		.aistma-data-card .data-card-label {
			font-size: 11px;
			color: #666;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin: 5px 0;
			font-weight: 500;
		}
		.aistma-data-card .data-card-trend {
			font-size: 10px;
			color: #888;
			margin-top: 5px;
		}
		.aistma-data-card .trend-indicator {
			font-size: 12px;
			margin-right: 3px;
		}
		.aistma-recent-posts-quick {
			margin-bottom: 15px;
			border-top: 1px solid #eee;
			padding-top: 15px;
		}
		.aistma-recent-posts-quick h4 {
			margin: 0 0 10px 0;
			font-size: 13px;
			color: #2c3e50;
			font-weight: 600;
		}
		.recent-posts-list {
			margin: 0;
			padding: 0;
			list-style: none;
		}
		.recent-post-item {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			padding: 5px 0;
			border-bottom: 1px solid #f5f5f5;
			gap: 10px;
		}
		.recent-post-item:last-child {
			border-bottom: none;
		}
		.recent-post-item .post-title {
			flex: 1;
			color: #0073aa;
			text-decoration: none;
			font-size: 12px;
			line-height: 1.3;
		}
		.recent-post-item .post-title:hover {
			text-decoration: underline;
		}
		.recent-post-item .post-date {
			font-size: 10px;
			color: #999;
			white-space: nowrap;
			flex-shrink: 0;
		}
		.aistma-widget-footer {
			text-align: center;
			border-top: 1px solid #eee;
			padding-top: 12px;
		}
		.aistma-widget-footer .button {
			margin: 0 2px;
			font-size: 11px;
			padding: 4px 8px;
			height: auto;
			line-height: 1.2;
		}
		
		/* Responsive adjustments */
		@media (max-width: 782px) {
			.aistma-data-cards-grid {
				grid-template-columns: repeat(2, 1fr);
			}
			.recent-post-item {
				flex-direction: column;
				align-items: flex-start;
				gap: 2px;
			}
		}
		';
	}

	/**
	 * Get widget-specific JavaScript
	 */
	private static function get_widget_scripts() {
		return '
		document.addEventListener("DOMContentLoaded", function() {
			// Add hover effects and interactions for data cards
			const dataCards = document.querySelectorAll(".aistma-data-card");
			dataCards.forEach(card => {
				card.addEventListener("click", function() {
					// Future: Add click functionality for data drilling
					console.log("Data card clicked:", this.querySelector(".data-card-label").textContent);
				});
			});
		});
		';
	}
}

// Initialize the widget
AISTMA_Data_Cards_Widget::init();
