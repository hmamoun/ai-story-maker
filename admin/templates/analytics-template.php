<?php
/**
 * Analytics Template for AI Story Maker.
 *
 * This template contains comprehensive analytics and insights about
 * AI story generation including activity visualization, statistics,
 * and performance metrics.
 * 
 * Note: Individual analytics components are also available as
 * dashboard widgets located in admin/widgets/ directory.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker/wiki
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Get story generation data for the heatmap
function aistma_get_story_generation_data() {
	global $wpdb;
	
	// Get stories created in the last 6 months (180 days)
	$six_months_ago = gmdate('Y-m-d', strtotime('-180 days'));
	
	// First try to get AI-generated posts
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Analytics aggregation query for custom data
	$ai_results = $wpdb->get_results($wpdb->prepare(
		"SELECT DATE(post_date) as date, COUNT(*) as count 
		FROM {$wpdb->posts} 
		WHERE post_type = 'post' 
		AND post_status = 'publish' 
		AND post_date >= %s
		AND ID IN (
			SELECT post_id FROM {$wpdb->postmeta} 
			WHERE meta_key = 'ai_story_maker_generated_via'
		)
		GROUP BY DATE(post_date)
		ORDER BY date ASC",
		$six_months_ago
	));
	
	// If no AI-generated posts found, get all published posts
	if (empty($ai_results)) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fallback analytics query
		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT DATE(post_date) as date, COUNT(*) as count 
			FROM {$wpdb->posts} 
			WHERE post_type = 'post' 
			AND post_status = 'publish' 
			AND post_date >= %s
			GROUP BY DATE(post_date)
			ORDER BY date ASC",
			$six_months_ago
		));
	} else {
		$results = $ai_results;
	}
	
	$data = array();
	foreach ($results as $row) {
		$data[$row->date] = (int)$row->count;
	}
	
	return $data;
}

// Generate calendar data
function aistma_generate_calendar_data($story_data, $hide_empty = false) {
	$calendar = array();
	$month_labels = array(); // Store month labels for columns that have first day
	$start_date = new DateTime('-180 days'); // 6 months ago
	$end_date = new DateTime('180 days');
	
	for ($date = clone $start_date; $date <= $end_date; $date->add(new DateInterval('P1D'))) {
		$date_str = $date->format('Y-m-d');
		$week = $date->format('W');
		$day_of_week = $date->format('N') - 1; // 0-6 (Monday-Sunday)
		$is_first_day_of_month = $date->format('j') === '1';
		
		if (!isset($calendar[$week])) {
			$calendar[$week] = array_fill(0, 7, array('count' => 0, 'is_first_day' => false));
		}
		
		$calendar[$week][$day_of_week] = array(
			'count' => isset($story_data[$date_str]) ? $story_data[$date_str] : 0,
			'is_first_day' => $is_first_day_of_month
		);
		
		// Store month label for weeks with first day of month
		if ($is_first_day_of_month) {
			$month_labels[$week] = $date->format('M'); // 3-letter month name
		}
	}
	
	// Filter out empty weeks if requested
	if ($hide_empty) {
		$filtered_calendar = array();
		$filtered_month_labels = array();
		
		foreach ($calendar as $week_num => $week_data) {
			$total_count = 0;
			foreach ($week_data as $day_data) {
				$total_count += is_array($day_data) ? $day_data['count'] : $day_data;
			}
			if ($total_count > 0) {
				$filtered_calendar[$week_num] = $week_data;
				if (isset($month_labels[$week_num])) {
					$filtered_month_labels[$week_num] = $month_labels[$week_num];
				}
			}
		}
		$calendar = $filtered_calendar;
		$month_labels = $filtered_month_labels;
	}
	
	return array('calendar' => $calendar, 'month_labels' => $month_labels);
}

// Initialize data
$story_data = aistma_get_story_generation_data();
$total_stories = array_sum($story_data);
$max_stories_per_day = !empty($story_data) ? max($story_data) : 0;
$hide_empty_columns = aistma_get_hide_empty_columns();
$calendar_result = aistma_generate_calendar_data($story_data, $hide_empty_columns);
$calendar_data = $calendar_result['calendar'];
$month_labels = $calendar_result['month_labels'];

// Get debug/stats information
global $wpdb;
$debug_info = array();
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Debug statistics for analytics dashboard
$debug_info['total_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'");
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- AI content statistics
$debug_info['ai_generated_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'post' AND p.post_status = 'publish' AND pm.meta_key = 'ai_story_maker_generated_via'");
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Recent posts for dashboard display
$debug_info['recent_posts'] = $wpdb->get_results("SELECT ID, post_title, post_date FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 5");
$debug_info['story_data_count'] = count($story_data);
$debug_info['total_stories'] = $total_stories;

// Get configurable widget settings
function aistma_get_activity_days() {
	return (int) get_option( 'aistma_widget_activity_days', 14 );
}

function aistma_get_recent_posts_limit() {
	return (int) get_option( 'aistma_widget_recent_posts_limit', 5 );
}

function aistma_get_hide_empty_columns() {
	return (bool) get_option( 'aistma_widget_hide_empty_columns', false );
}

// Get total clicks by post tag
function aistma_get_tag_clicks_data() {
	global $wpdb;
	
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Complex analytics query for tag click data
	$results = $wpdb->get_results(
		"SELECT 
			t.name as tag_name,
			t.term_id as tag_id,
			COUNT(ti.id) as total_clicks
		FROM {$wpdb->terms} t
		INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
		INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
		INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
		INNER JOIN {$wpdb->prefix}aistma_traffic_info ti ON p.ID = ti.post_id
		WHERE tt.taxonomy = 'post_tag'
		AND p.post_type = 'post'
		AND p.post_status = 'publish'
		GROUP BY t.term_id, t.name
		ORDER BY total_clicks DESC
		LIMIT 20"
	);
	
	return $results;
}

// Get tag clicks data
$tag_clicks_data = aistma_get_tag_clicks_data();

// Build Post Views Heatmap data (recent posts x last N days)
$aistma_views_days_window = aistma_get_activity_days();
$recent_posts_limit = aistma_get_recent_posts_limit();

// Get recent posts with configurable limit
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Recent posts query for analytics display
$recent_posts_list = $wpdb->get_results( $wpdb->prepare(
	"SELECT ID, post_title, post_date FROM {$wpdb->posts} 
	WHERE post_type = 'post' AND post_status = 'publish' 
	ORDER BY post_date DESC LIMIT %d",
	$recent_posts_limit
) );

// Ensure we have IDs only for recent posts
$recent_post_ids = array();
foreach ( $recent_posts_list as $rp ) {
    $recent_post_ids[] = (int) $rp->ID;
}

$post_views_by_day = array();
$date_labels = array();
$max_views_per_cell = 0;

if ( ! empty( $recent_post_ids ) ) {
    
    // Generate date labels for the past N days
    for ( $i = $aistma_views_days_window - 1; $i >= 0; $i-- ) {
        $date_labels[] = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
    }
    
    // For each recent post, build views array per day
    foreach ( $recent_post_ids as $pid ) {
        $post_views_by_day[ $pid ] = array();
        
        // Initialize all days to 0
        for ( $i = 0; $i < count( $date_labels ); $i++ ) {
            $post_views_by_day[ $pid ][ $i ] = 0;
        }
        
        // Get actual views data from aistma_traffic_info table
        for ( $i = 0; $i < count( $date_labels ); $i++ ) {
            $date = $date_labels[ $i ];
            
            // Count views for this post on this specific date
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Traffic analytics from custom table
            $view_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aistma_traffic_info 
                WHERE post_id = %d 
                AND DATE(viewed_at) = %s",
                $pid,
                $date
            ) );
            
            $actual_views = (int) $view_count;
            $post_views_by_day[ $pid ][ $i ] = $actual_views;
            
            if ( $actual_views > $max_views_per_cell ) {
                $max_views_per_cell = $actual_views;
            }
        }
    }
}

// Filter empty columns for activity heatmap if requested
if ( $hide_empty_columns && ! empty( $post_views_by_day ) && ! empty( $date_labels ) ) {
    $filtered_date_labels = array();
    $filtered_post_views_by_day = array();
    
    // Check each day to see if any post has views
    for ( $i = 0; $i < count( $date_labels ); $i++ ) {
        $has_activity = false;
        foreach ( $recent_post_ids as $pid ) {
            if ( isset( $post_views_by_day[ $pid ][ $i ] ) && $post_views_by_day[ $pid ][ $i ] > 0 ) {
                $has_activity = true;
                break;
            }
        }
        
        // If this day has activity, include it
        if ( $has_activity ) {
            $new_index = count( $filtered_date_labels );
            $filtered_date_labels[] = $date_labels[ $i ];
            
            // Copy views for this day to the filtered array
            foreach ( $recent_post_ids as $pid ) {
                if ( ! isset( $filtered_post_views_by_day[ $pid ] ) ) {
                    $filtered_post_views_by_day[ $pid ] = array();
                }
                $filtered_post_views_by_day[ $pid ][ $new_index ] = isset( $post_views_by_day[ $pid ][ $i ] ) ? $post_views_by_day[ $pid ][ $i ] : 0;
            }
        }
    }
    
    // Use filtered data if we have any days with activity
    if ( ! empty( $filtered_date_labels ) ) {
        $date_labels = $filtered_date_labels;
        $post_views_by_day = $filtered_post_views_by_day;
    }
}
?>
<div class="wrapper">
<div class="aistma-style-settings">
<div class="aistma-heatmap-calendar-wrapper">
	<h2><?php esc_html_e('Analytics Dashboard', 'ai-story-maker'); ?></h2>


		<div class="aistma-heatmap-header">

			
			<div class="aistma-analytic-block aistma-debug-cards">
				<div class="aistma-debug-card">
					<div class="debug-card-number"><?php echo esc_html($debug_info['total_posts']); ?></div>
					<div class="debug-card-caption"><?php esc_html_e('Total Posts', 'ai-story-maker'); ?></div>
				</div>
				<div class="aistma-debug-card">
					<div class="debug-card-number"><?php echo esc_html($debug_info['ai_generated_posts']); ?></div>
					<div class="debug-card-caption"><?php esc_html_e('AI Generated', 'ai-story-maker'); ?></div>
				</div>
				<div class="aistma-debug-card">
					<div class="debug-card-number"><?php echo esc_html($total_stories); ?></div>
					<div class="debug-card-caption"><?php esc_html_e('Stories (6 months)', 'ai-story-maker'); ?></div>
				</div>
				<div class="aistma-debug-card">
					<div class="debug-card-number"><?php echo esc_html($max_stories_per_day); ?></div>
					<div class="debug-card-caption"><?php esc_html_e('Max/Day', 'ai-story-maker'); ?></div>
				</div>
		
			<div class="aistma-insight-tips">
				<ul>
					<li><?php esc_html_e('Total Posts shows content volume; track growth month-over-month.', 'ai-story-maker'); ?></li>
					<li><?php esc_html_e('AI Generated indicates adoption; if low, promote AI workflows and templates.', 'ai-story-maker'); ?></li>
					<li><?php esc_html_e('Stories (6 months) reveals output trend; set and review monthly targets.', 'ai-story-maker'); ?></li>
					<li><?php esc_html_e('Max/Day highlights peak capacity; schedule content sprints around peak days.', 'ai-story-maker'); ?></li>
				</ul>
			</div>
			</div>
		</div>

		<!-- Story Generation Calendar Heatmap -->
		<div class="aistma-analytic-block">
			<h4><?php esc_html_e('Story Generation Calendar (last 6 months)', 'ai-story-maker'); ?></h4>
			<div class="aistma-heatmap-legend">
				<span><?php esc_html_e('Less', 'ai-story-maker'); ?></span>
				<div class="legend-squares">
					<div class="legend-square intensity-0"></div>
					<div class="legend-square intensity-1"></div>
					<div class="legend-square intensity-2"></div>
					<div class="legend-square intensity-3"></div>
					<div class="legend-square intensity-4"></div>
				</div>
				<span><?php esc_html_e('More', 'ai-story-maker'); ?></span>
			</div>
			
			<div class="aistma-heatmap-grid">
				<div class="aistma-heatmap-container-vertical">
					<!-- Month labels row -->
					<div class="aistma-heatmap-month-labels">
						<div class="month-label-spacer"></div>
						<?php foreach ($calendar_data as $week_num => $week_data): ?>
							<div class="month-label-vertical">
								<?php if (isset($month_labels[$week_num])): ?>
									<span class="month-text"><?php echo esc_html($month_labels[$week_num]); ?></span>
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
						<?php foreach ($calendar_data as $week_num => $week_data): ?>
							<div class="aistma-heatmap-week-vertical">
								<?php foreach ($week_data as $day_index => $day_data): ?>
									<?php 
									$story_count = $day_data['count'];
									$is_first_day = $day_data['is_first_day'];
									
									$intensity_class = 'intensity-0';
									if ($story_count > 0) {
										if ($story_count <= 2) $intensity_class = 'intensity-1';
										elseif ($story_count <= 5) $intensity_class = 'intensity-2';
										elseif ($story_count <= 10) $intensity_class = 'intensity-3';
										else $intensity_class = 'intensity-4';
									}
									
									$css_classes = $intensity_class;
									if ($is_first_day) {
										$css_classes .= ' first-day-of-month';
									}
									?>
									<div class="aistma-heatmap-day <?php echo esc_attr($css_classes); ?>" 
										 data-stories="<?php echo esc_attr($story_count); ?>"
										 data-week="<?php echo esc_attr($week_num); ?>"
										 data-day="<?php echo esc_attr($day_index); ?>">
										<?php if ($story_count > 0): ?>
											<span class="day-tooltip"><?php echo esc_html($story_count); ?> <?php esc_html_e('stories', 'ai-story-maker'); ?></span>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>


	<div class="aistma-insight-tips">
		<ul>
			<li><?php esc_html_e('Darker squares = more stories that day; scan for streaks or gaps.', 'ai-story-maker'); ?></li>
			<li><?php esc_html_e('Use month markers to compare periods; note seasonal patterns.', 'ai-story-maker'); ?></li>
			<li><?php esc_html_e('Cold weeks suggest low output; plan short content sprints to fill gaps.', 'ai-story-maker'); ?></li>
			<li><?php esc_html_e('Hot periods indicate what works; replicate prompts, timing, or promotion.', 'ai-story-maker'); ?></li>
		</ul>
	</div>
	</div>
		<!-- Recent Posts x Days Heatmap -->
        <div class="aistma-analytic-block" style="margin-top:20px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
				<h4><?php
				/* translators: %d: number of days shown in the recent posts activity heatmap. */
				echo esc_html( sprintf( __( 'Recent Posts Activity (last %d days)', 'ai-story-maker' ), $aistma_views_days_window ) );
				?></h4>
				<button id="aistma-analytics-settings-btn" class="button button-secondary" style="font-size: 12px;">
					<?php esc_html_e( 'Settings', 'ai-story-maker' ); ?>
				</button>
			</div>
			<div class="aistma-heatmap-grid">
				<div class="aistma-heatmap-container-horizontal">
					<!-- Column headers: dates -->
                    <div class="aistma-heatmap-dates" style="grid-template-columns: 220px repeat(<?php echo (int) count( $date_labels ); ?>, 16px);">
						<div class="post-label">&nbsp;</div>
						<?php foreach ( $date_labels as $dlabel ) : ?>
							<div class="date-vertical"><?php echo esc_html( date_i18n( 'M j', strtotime( $dlabel ) ) ); ?></div>
						<?php endforeach; ?>
					</div>
					<!-- Rows: each recent post -->
                    <?php foreach ( $recent_posts_list as $rp ) : $pid = (int) $rp->ID; ?>
                        <div class="aistma-heatmap-row" style="grid-template-columns: 220px repeat(<?php echo (int) count( $date_labels ); ?>, 16px);">
							<div class="post-label"><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank"><?php echo esc_html( wp_html_excerpt( $rp->post_title ?: __('(no title)', 'ai-story-maker'), 40, '…' ) ); ?></a></div>
							<?php for ( $i = 0; $i < count( $date_labels ); $i++ ) :
								$views = isset( $post_views_by_day[ $pid ][ $i ] ) ? (int) $post_views_by_day[ $pid ][ $i ] : 0;
								$intensity = 'intensity-0';
								if ( $views > 0 ) {
									if ( $views <= 2 ) $intensity = 'intensity-1';
									elseif ( $views <= 5 ) $intensity = 'intensity-2';
									elseif ( $views <= 10 ) $intensity = 'intensity-3';
									else $intensity = 'intensity-4';
								}
							?>
							<div class="aistma-heatmap-day <?php echo esc_attr( $intensity ); ?>" title="<?php echo esc_attr( $views . ' views' ); ?>"></div>
							<?php endfor; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>


		<div class="aistma-insight-tips">
			<ul>
				<li><?php esc_html_e('Rows are posts; columns are days; darker cells = more views.', 'ai-story-maker'); ?></li>
				<li><?php esc_html_e('Consistent heat = evergreen; add internal links and repurpose content.', 'ai-story-maker'); ?></li>
				<li><?php esc_html_e('Cold posts need work: improve titles/SEO, refresh content, or promote.', 'ai-story-maker'); ?></li>
				<li><?php esc_html_e('View spikes? Find the source (referrers/social) and amplify quickly.', 'ai-story-maker'); ?></li>
			</ul>
		</div>
		</div>
		<!-- Total Clicks by Post Tag -->
		<div class="aistma-analytic-block " style="margin-top:20px;">
			<h4><?php esc_html_e('Total Clicks by Post Tag', 'ai-story-maker'); ?></h4>
			<?php if (!empty($tag_clicks_data)): ?>
				<div class="aistma-tag-clicks-container">
					<div class="aistma-tag-clicks-header">
						<div class="tag-name-header"><?php esc_html_e('Tag Name', 'ai-story-maker'); ?></div>
						<div class="tag-clicks-header"><?php esc_html_e('Total Clicks', 'ai-story-maker'); ?></div>
						<div class="tag-percentage-header"><?php esc_html_e('Percentage', 'ai-story-maker'); ?></div>
					</div>
					<div class="aistma-tag-clicks-list">
						<?php 
						$total_all_clicks = array_sum(array_column($tag_clicks_data, 'total_clicks'));
						foreach ($tag_clicks_data as $tag_data): 
							$percentage = $total_all_clicks > 0 ? round(($tag_data->total_clicks / $total_all_clicks) * 100, 1) : 0;
							$tag_url = get_tag_link($tag_data->tag_id);
						?>
							<div class="aistma-tag-click-item">
								<div class="tag-name">
									<a href="<?php echo esc_url($tag_url); ?>" target="_blank" title="<?php echo esc_attr($tag_data->tag_name); ?>">
										<?php echo esc_html($tag_data->tag_name); ?>
									</a>
								</div>
								<div class="tag-clicks">
									<span class="clicks-number"><?php echo esc_html(number_format($tag_data->total_clicks)); ?></span>
									<div class="clicks-bar">
										<div class="clicks-bar-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
									</div>
								</div>
								<div class="tag-percentage">
									<span><?php echo esc_html($percentage); ?>%</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="aistma-tag-clicks-summary">
						<div class="summary-item">
							<strong><?php esc_html_e('Total Tags:', 'ai-story-maker'); ?></strong> 
							<span><?php echo esc_html(count($tag_clicks_data)); ?></span>
						</div>
						<div class="summary-item">
							<strong><?php esc_html_e('Total Clicks:', 'ai-story-maker'); ?></strong> 
							<span><?php echo esc_html(number_format($total_all_clicks)); ?></span>
						</div>
						<?php if (!empty($tag_clicks_data)): ?>
						<div class="summary-item">
							<strong><?php esc_html_e('Top Tag:', 'ai-story-maker'); ?></strong> 
							<span><?php echo esc_html($tag_clicks_data[0]->tag_name); ?> (<?php echo esc_html(number_format($tag_clicks_data[0]->total_clicks)); ?>)</span>
						</div>
						<?php endif; ?>
					</div>
				</div>
			<?php else: ?>
				<div class="aistma-no-data">
					<p><?php esc_html_e('No tag click data available. Tags need to be assigned to posts and posts need to receive traffic to show analytics.', 'ai-story-maker'); ?></p>
				</div>
			<?php endif; ?>
			<div class="aistma-insight-tips">
				<ul>
					<li><?php esc_html_e('Tags ranked by total clicks; top tags signal audience interest.', 'ai-story-maker'); ?></li>
					<li><?php esc_html_e('Double down on top tags with new posts and hub pages.', 'ai-story-maker'); ?></li>
					<li><?php esc_html_e('Low-performing tags: consolidate, retag, or improve navigation.', 'ai-story-maker'); ?></li>
					<li><?php esc_html_e('Ensure posts are properly tagged to capture intent and improve discovery.', 'ai-story-maker'); ?></li>
				</ul>
			</div>
		</div>


	</div>

	<!-- Analytics Settings Modal -->
	<div id="aistma-analytics-settings-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
		<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); min-width: 400px;">
			<h3><?php esc_html_e( 'Analytics Settings', 'ai-story-maker' ); ?></h3>
			<form id="aistma-analytics-settings-form">
				<p>
					<label for="aistma_analytics_activity_days"><?php esc_html_e( 'Number of days to show:', 'ai-story-maker' ); ?></label><br>
					<input type="number" id="aistma_analytics_activity_days" name="aistma_analytics_activity_days" 
						   value="<?php echo esc_attr( $aistma_views_days_window ); ?>" min="1" max="90" style="width: 80px;" />
					<small><?php esc_html_e( '(1-90 days)', 'ai-story-maker' ); ?></small>
				</p>
				<p>
					<label for="aistma_analytics_recent_posts_limit"><?php esc_html_e( 'Number of posts to show:', 'ai-story-maker' ); ?></label><br>
					<input type="number" id="aistma_analytics_recent_posts_limit" name="aistma_analytics_recent_posts_limit" 
						   value="<?php echo esc_attr( $recent_posts_limit ); ?>" min="1" max="20" style="width: 80px;" />
					<small><?php esc_html_e( '(1-20 posts)', 'ai-story-maker' ); ?></small>
				</p>
				<p>
					<label>
						<input type="checkbox" id="aistma_analytics_hide_empty_columns" name="aistma_analytics_hide_empty_columns" 
							   value="1" <?php checked( aistma_get_hide_empty_columns() ); ?> />
						<?php esc_html_e( 'Hide empty columns (days/weeks with no activity)', 'ai-story-maker' ); ?>
					</label>
				</p>
				<div style="margin-top: 20px; text-align: right;">
					<button type="button" id="aistma-analytics-settings-cancel" class="button" style="margin-right: 10px;">
						<?php esc_html_e( 'Cancel', 'ai-story-maker' ); ?>
					</button>
					<button type="button" id="aistma-analytics-settings-save" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'ai-story-maker' ); ?>
					</button>
				</div>
				<div id="aistma-analytics-settings-status" style="margin-top: 10px; text-align: center;"></div>
				<?php wp_nonce_field( 'aistma_widget_config', 'aistma_analytics_settings_nonce' ); ?>
			</form>
		</div>
	</div>

</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {


	// Add click handlers for heatmap days
	const heatmapDays = document.querySelectorAll('.aistma-heatmap-day');
	
	heatmapDays.forEach(day => {
		day.addEventListener('click', function() {
			const stories = this.getAttribute('data-stories');
			const week = this.getAttribute('data-week');
			const dayIndex = this.getAttribute('data-day');
			
			if (stories > 0) {
				// You can add additional functionality here, like showing a modal with detailed stats
				console.log(`Week ${week}, Day ${dayIndex}: ${stories} stories`);
			}
		});
	});
	
	// Add keyboard navigation
	heatmapDays.forEach((day, index) => {
		day.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				this.click();
			}
		});
		
		// Make days focusable
		day.setAttribute('tabindex', '0');
		day.setAttribute('role', 'button');
		day.setAttribute('aria-label', `Day with ${day.getAttribute('data-stories')} stories generated`);
	});

	// Simple tabs logic for Weekly Views section (if needed in future)
	const tabBtns = document.querySelectorAll('.aistma-views-tabs .tab-btn');
	const tabPanes = document.querySelectorAll('.aistma-views-tabs .tab-pane');
	tabBtns.forEach(btn => {
		btn.addEventListener('click', () => {
			tabBtns.forEach(b => b.classList.remove('active'));
			tabPanes.forEach(p => p.classList.remove('active'));
			btn.classList.add('active');
			document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
		});
	});

	// Tag clicks interactions
	const tagClickItems = document.querySelectorAll('.aistma-analytic-block .aistma-tag-click-item');
	tagClickItems.forEach(item => {
		// Add click handler for tag items
		item.addEventListener('click', function(e) {
			// Don't trigger if clicking on the tag link itself
			if (e.target.tagName === 'A') {
				return;
			}
			
			const tagName = this.querySelector('.tag-name a').textContent;
			const clicksNumber = this.querySelector('.clicks-number').textContent;
			const percentage = this.querySelector('.tag-percentage span').textContent;
			
			// Create a simple tooltip or alert with tag details
			console.log(`Tag: ${tagName}, Clicks: ${clicksNumber}, Percentage: ${percentage}`);
			
			// You can enhance this to show a modal or detailed view
			// For now, we'll just highlight the item
			this.style.backgroundColor = '#e8f4fd';
			setTimeout(() => {
				this.style.backgroundColor = '';
			}, 1000);
		});
		
		// Add hover effects
		item.addEventListener('mouseenter', function() {
			const clicksBar = this.querySelector('.clicks-bar-fill');
			if (clicksBar) {
				clicksBar.style.transform = 'scaleY(1.1)';
				clicksBar.style.transformOrigin = 'bottom';
			}
		});
		
		item.addEventListener('mouseleave', function() {
			const clicksBar = this.querySelector('.clicks-bar-fill');
			if (clicksBar) {
				clicksBar.style.transform = 'scaleY(1)';
			}
		});
		
		// Make items keyboard accessible
		item.setAttribute('tabindex', '0');
		item.setAttribute('role', 'button');
		
		item.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				this.click();
			}
		});
	});

	// Analytics Settings Modal
	const settingsBtn = document.getElementById('aistma-analytics-settings-btn');
	const settingsModal = document.getElementById('aistma-analytics-settings-modal');
	const settingsCancelBtn = document.getElementById('aistma-analytics-settings-cancel');
	const settingsSaveBtn = document.getElementById('aistma-analytics-settings-save');
	const settingsStatus = document.getElementById('aistma-analytics-settings-status');

	// Show modal
	if (settingsBtn) {
		settingsBtn.addEventListener('click', function(e) {
			e.preventDefault();
			settingsModal.style.display = 'block';
		});
	}

	// Hide modal
	function hideModal() {
		settingsModal.style.display = 'none';
		settingsStatus.textContent = '';
	}

	if (settingsCancelBtn) {
		settingsCancelBtn.addEventListener('click', hideModal);
	}

	// Click outside modal to close
	settingsModal.addEventListener('click', function(e) {
		if (e.target === settingsModal) {
			hideModal();
		}
	});

	// Save settings
	if (settingsSaveBtn) {
		settingsSaveBtn.addEventListener('click', function(e) {
			e.preventDefault();
			
			const activityDays = document.getElementById('aistma_analytics_activity_days').value;
			const recentPostsLimit = document.getElementById('aistma_analytics_recent_posts_limit').value;
			const hideEmptyColumns = document.getElementById('aistma_analytics_hide_empty_columns').checked ? 1 : 0;
			const nonce = document.getElementById('aistma_analytics_settings_nonce').value;
			
			settingsSaveBtn.disabled = true;
			settingsStatus.textContent = '<?php esc_html_e( 'Saving...', 'ai-story-maker' ); ?>';
			settingsStatus.style.color = '#666';
			
			fetch(ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'aistma_save_widget_config',
					activity_days: activityDays,
					recent_posts_limit: recentPostsLimit,
					hide_empty_columns: hideEmptyColumns,
					nonce: nonce
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					settingsStatus.textContent = '<?php esc_html_e( 'Settings saved! Refreshing page...', 'ai-story-maker' ); ?>';
					settingsStatus.style.color = 'green';
					setTimeout(() => {
						window.location.reload();
					}, 1500);
				} else {
					settingsStatus.textContent = '<?php esc_html_e( 'Error saving settings.', 'ai-story-maker' ); ?>';
					settingsStatus.style.color = 'red';
				}
			})
			.catch(error => {
				settingsStatus.textContent = '<?php esc_html_e( 'Error saving settings.', 'ai-story-maker' ); ?>';
				settingsStatus.style.color = 'red';
			})
			.finally(() => {
				settingsSaveBtn.disabled = false;
				setTimeout(() => {
					if (settingsStatus.style.color !== 'green') {
						settingsStatus.textContent = '';
					}
				}, 3000);
			});
		});
	}

});
</script>

