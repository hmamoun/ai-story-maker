<?php
/**
 * Story Generation Calendar Dashboard Widget
 *
 * WordPress dashboard widget displaying story generation activity
 * in a 6-month calendar heatmap format.
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
 * Class AISTMA_Story_Calendar_Widget
 * 
 * Dashboard widget for story generation calendar visualization
 */
class AISTMA_Story_Calendar_Widget {

	/**
	 * Widget ID
	 */
	const WIDGET_ID = 'aistma_story_calendar_widget';

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
		if ( current_user_can( 'manage_options' ) ) {
			wp_add_dashboard_widget(
				self::WIDGET_ID,
				__( 'Story Generation Calendar', 'ai-story-maker' ),
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
	 * Get story generation data for the heatmap
	 */
	private static function get_story_generation_data() {
		global $wpdb;
		
		// Get stories created in the last 2 months (60 days)
		// Get stories created in the last 3 months (90 days)
		$three_months_ago = gmdate( 'Y-m-d', strtotime( '-90 days' ) );
		
		// First try to get AI-generated posts
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- AI story analytics for calendar heatmap
		$ai_results = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(post_date) as date, COUNT(*) as count 
			FROM {$wpdb->posts} 
			WHERE post_type = 'post' 
			AND post_status = 'publish' 
			AND post_date >= %s
			AND ID IN (
				SELECT post_id FROM {$wpdb->postmeta} 
				WHERE meta_key = '_aistma_generated'
			)
			GROUP BY DATE(post_date)
			ORDER BY date ASC",
			$three_months_ago
		) );
		
		// If no AI-generated posts found, get all published posts
		if ( empty( $ai_results ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fallback story data for calendar
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT DATE(post_date) as date, COUNT(*) as count 
				FROM {$wpdb->posts} 
				WHERE post_type = 'post' 
				AND post_status = 'publish' 
				AND post_date >= %s
				GROUP BY DATE(post_date)
				ORDER BY date ASC",
				$three_months_ago
			) );
		} else {
			$results = $ai_results;
		}
		
		$data = array();
		foreach ( $results as $row ) {
			if ( $row && isset( $row->date, $row->count ) ) {
				$data[ $row->date ] = (int) $row->count;
			}
		}
		
		return $data;
	}

	/**
	 * Generate calendar data
	 */
	private static function generate_calendar_data( $story_data ) {
		$calendar = array();
		$month_labels = array(); // Store month labels by exact position
		$start_date = new DateTime( '-90 days' ); // 3 months ago
		$end_date = new DateTime( 'now' );
		
		for ( $date = clone $start_date; $date <= $end_date; $date->add( new DateInterval( 'P1D' ) ) ) {
			$date_str = $date->format( 'Y-m-d' );
			$week = $date->format( 'W' );
			$day_of_week = $date->format( 'N' ) - 1; // 0-6 (Monday-Sunday)
			$is_first_day_of_month = $date->format( 'j' ) === '1';
			
			if ( ! isset( $calendar[ $week ] ) ) {
				$calendar[ $week ] = array_fill( 0, 7, array( 'count' => 0, 'is_first_day' => false ) );
			}
			
			$calendar[ $week ][ $day_of_week ] = array(
				'count' => isset( $story_data[ $date_str ] ) ? $story_data[ $date_str ] : 0,
				'is_first_day' => $is_first_day_of_month
			);
			
			// Store month label for the exact position where first day appears
			if ( $is_first_day_of_month ) {
				$position_key = $week . '-' . $day_of_week;
				$month_labels[ $position_key ] = $date->format( 'M' ); // 3-letter month name
			}
		}
		
		return array( 'calendar' => $calendar, 'month_labels' => $month_labels );
	}

	/**
	 * Render the dashboard widget
	 */
	public static function render_widget() {
		$story_data = self::get_story_generation_data();
		$calendar_result = self::generate_calendar_data( $story_data );
		$calendar_data = $calendar_result['calendar'];
		$month_labels = $calendar_result['month_labels'];
		$total_stories = array_sum( $story_data );
		?>
		<div class="aistma-story-calendar-widget">
			<div class="aistma-widget-summary">
			<p><strong><?php echo esc_html( $total_stories ); ?></strong> <?php esc_html_e( 'stories generated in the last 3 months', 'ai-story-maker' ); ?></p>
			</div>

			<div class="aistma-heatmap-legend">
				<span><?php esc_html_e( 'Less', 'ai-story-maker' ); ?></span>
				<div class="legend-squares">
					<div class="legend-square intensity-0"></div>
					<div class="legend-square intensity-1"></div>
					<div class="legend-square intensity-2"></div>
					<div class="legend-square intensity-3"></div>
					<div class="legend-square intensity-4"></div>
					</div>
		
				<span><?php esc_html_e( 'More', 'ai-story-maker' ); ?></span>
				<div class="legend-square intensity-0 first-day-of-month" title="<?php esc_attr_e( 'First day of month', 'ai-story-maker' ); ?>"></div>
				<span><?php esc_html_e( 'First day of month', 'ai-story-maker' ); ?></span>
			</div>
			
			<div class="aistma-heatmap-grid">
				<div class="aistma-heatmap-container-vertical">
					<!-- Month labels row -->
					<div class="aistma-heatmap-month-labels">
						<div class="month-label-spacer"></div>
						<?php foreach ( $calendar_data as $week_num => $week_data ) : ?>
							<div class="month-label-vertical">
								<?php if ( isset( $month_labels[ $week_num ] ) ) : ?>
									<span class="month-text"><?php echo esc_html( $month_labels[ $week_num ] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="aistma-heatmap-weeks-container">
						<div class="aistma-heatmap-weekdays-vertical">
							<div class="weekday-vertical"><?php esc_html_e('M', 'ai-story-maker'); ?></div>
							<div class="weekday-vertical"><?php esc_html_e('T', 'ai-story-maker'); ?></div>
							<div class="weekday-vertical"><?php esc_html_e('W', 'ai-story-maker'); ?></div>
							<div class="weekday-vertical"><?php esc_html_e('T', 'ai-story-maker'); ?></div>
							<div class="weekday-vertical"><?php esc_html_e('F', 'ai-story-maker'); ?></div>
							<div class="weekday-vertical"><?php esc_html_e('S', 'ai-story-maker'); ?></div>
							<div class="weekday-vertical"><?php esc_html_e('S', 'ai-story-maker'); ?></div>
						</div>
						
						<div class="aistma-heatmap-weeks-vertical">
						<?php foreach ( $calendar_data as $week_num => $week_data ) : ?>
							<div class="aistma-heatmap-week-vertical">
								<?php foreach ( $week_data as $day_index => $day_data ) : ?>
									<?php 
									$story_count = $day_data['count'];
									$is_first_day = $day_data['is_first_day'];
									
									$intensity_class = 'intensity-0';
									if ( $story_count > 0 ) {
										if ( $story_count <= 2 ) {
											$intensity_class = 'intensity-1';
										} elseif ( $story_count <= 5 ) {
											$intensity_class = 'intensity-2';
										} elseif ( $story_count <= 10 ) {
											$intensity_class = 'intensity-3';
										} else {
											$intensity_class = 'intensity-4';
										}
									}
									
									$css_classes = $intensity_class;
									if ( $is_first_day ) {
										$css_classes .= ' first-day-of-month';
									}
									?>
									<div class="aistma-heatmap-day <?php echo esc_attr( $css_classes ); ?>" 
										 data-stories="<?php echo esc_attr( $story_count ); ?>"
										 data-week="<?php echo esc_attr( $week_num ); ?>"
										 data-day="<?php echo esc_attr( $day_index ); ?>"
										 title="<?php 
										 /* translators: %d: number of stories generated on this day */
										 echo esc_attr( sprintf( __( '%d stories generated', 'ai-story-maker' ), $story_count ) ); ?>">
									</div>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
						</div>
					</div>
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
		.aistma-story-calendar-widget {
			--heatmap-square-size: 10px;
			font-size: 13px;
		}
		.aistma-story-calendar-widget .aistma-widget-summary {
			margin-bottom: 15px;
			padding: 10px;
			background: #f8f9fa;
			border-radius: 4px;
			text-align: center;
		}
		.aistma-story-calendar-widget .aistma-heatmap-legend {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 12px;
			font-size: 11px;
			color: #666;
			justify-content: center;
		}
		.aistma-story-calendar-widget .legend-squares {
			display: flex;
			gap: 2px;
		}
		.aistma-story-calendar-widget .legend-square {
			width: var(--heatmap-square-size);
			height: var(--heatmap-square-size);
			border-radius: 2px;
		}
		.aistma-story-calendar-widget .aistma-heatmap-container-vertical {
			display: flex;
			flex-direction: column;
			gap: 2px;
			align-items: center;
			justify-content: center;
		}
		.aistma-story-calendar-widget .aistma-heatmap-month-labels {
			display: flex;
			gap: 8px;
			margin-bottom: 5px;
			align-items: flex-end;
		}
		.aistma-story-calendar-widget .month-label-spacer {
			width: 33px;
		}
		.aistma-story-calendar-widget .month-labels-container {
			display: flex;
			gap: 1px;
		}
		.aistma-story-calendar-widget .month-label-week {
			display: flex;
			flex-direction: column;
			gap: 1px;
			justify-content: flex-end;
		}
		.aistma-story-calendar-widget .month-label-day {
			width: var(--heatmap-square-size);
			height: 15px;
			display: flex;
			align-items: flex-end;
			justify-content: center;
		}
		.aistma-story-calendar-widget .month-text {
			font-size: 10px;
			font-weight: 600;
			color: #666;
			writing-mode: horizontal-tb;
			text-orientation: mixed;
			letter-spacing: 0;
			transform: rotate(-90deg);
			white-space: nowrap;
		}
		.aistma-story-calendar-widget .aistma-heatmap-weeks-container {
			display: flex;
			gap: 8px;
			align-items: flex-start;
		}
		.aistma-story-calendar-widget .aistma-heatmap-weekdays-vertical {
			display: flex;
			flex-direction: column;
			gap: 1px;
			margin-right: 8px;
		}
		.aistma-story-calendar-widget .weekday-vertical {
			font-size: 9px;
			color: #666;
			text-align: center;
			font-weight: 500;
			height: var(--heatmap-square-size);
			line-height: var(--heatmap-square-size);
			width: 25px;
		}
		.aistma-story-calendar-widget .aistma-heatmap-weeks-vertical {
			display: flex;
			gap: 1px;
			overflow-x: auto;
			max-width: 300px;
		}
		.aistma-story-calendar-widget .aistma-heatmap-week-vertical {
			display: flex;
			flex-direction: column;
			gap: 1px;
		}
		.aistma-story-calendar-widget .aistma-heatmap-day {
			width: var(--heatmap-square-size);
			height: var(--heatmap-square-size);
			border-radius: 2px;
			transition: all 0.2s ease;
		}
		.aistma-story-calendar-widget .aistma-heatmap-day:hover {
			transform: scale(1.2);
			z-index: 10;
		}
		.aistma-story-calendar-widget .intensity-0 { background-color: #ebedf0; }
		.aistma-story-calendar-widget .intensity-1 { background-color: #c6e48b; }
		.aistma-story-calendar-widget .intensity-2 { background-color: #7bc96f; }
		.aistma-story-calendar-widget .intensity-3 { background-color: #239a3b; }
		.aistma-story-calendar-widget .intensity-4 { background-color: #196127; }
		.aistma-story-calendar-widget .first-day-of-month { border: 1px solid #000; }
		.aistma-story-calendar-widget .aistma-widget-footer {
			margin-top: 15px;
			text-align: center;
			border-top: 1px solid #eee;
			padding-top: 10px;
		}
		';
	}

	
}

// Initialize the widget
AISTMA_Story_Calendar_Widget::init();
