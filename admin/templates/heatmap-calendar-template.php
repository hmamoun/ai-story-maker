<?php
/**
 * Heatmap Calendar Template for AI Story Maker.
 *
 * This template contains the complete heatmap calendar functionality
 * including story generation data visualization and recent posts activity.
 * 
 * Note: This template now provides both a combined view and individual
 * dashboard widgets. The widgets can be managed through the WordPress
 * dashboard and are located in admin/widgets/ directory.
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

// Check if we should show widget promotion instead
$show_widget_promotion = apply_filters( 'aistma_show_widget_promotion', true );

// Get story generation data for the heatmap
function aistma_get_story_generation_data() {
	global $wpdb;
	
	// Get stories created in the last 6 months (180 days)
	$six_months_ago = date('Y-m-d', strtotime('-180 days'));
	
	// First try to get AI-generated posts
	$ai_results = $wpdb->get_results($wpdb->prepare(
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
		$six_months_ago
	));
	
	// If no AI-generated posts found, get all published posts
	if (empty($ai_results)) {
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
function aistma_generate_calendar_data($story_data) {
	$calendar = array();
	$start_date = new DateTime('-180 days'); // 6 months ago
	$end_date = new DateTime('180 days');
	
	for ($date = clone $start_date; $date <= $end_date; $date->add(new DateInterval('P1D'))) {
		$date_str = $date->format('Y-m-d');
		$week = $date->format('W');
		$day_of_week = $date->format('N') - 1; // 0-6 (Monday-Sunday)
		
		if (!isset($calendar[$week])) {
			$calendar[$week] = array_fill(0, 7, 0);
		}
		
		$calendar[$week][$day_of_week] = isset($story_data[$date_str]) ? $story_data[$date_str] : 0;
	}
	
	return $calendar;
}

// Initialize data
$story_data = aistma_get_story_generation_data();
$total_stories = array_sum($story_data);
$max_stories_per_day = !empty($story_data) ? max($story_data) : 0;
$calendar_data = aistma_generate_calendar_data($story_data);

// Get debug/stats information
global $wpdb;
$debug_info = array();
$debug_info['total_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'");
$debug_info['ai_generated_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'post' AND p.post_status = 'publish' AND pm.meta_key = '_aistma_generated'");
$debug_info['recent_posts'] = $wpdb->get_results("SELECT ID, post_title, post_date FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 5");
$debug_info['story_data_count'] = count($story_data);
$debug_info['total_stories'] = $total_stories;

// Build Post Views Heatmap data (recent posts x last N days)
$aistma_views_days_window = 14;
$recent_posts_limit = 5;
$recent_posts_list = $debug_info['recent_posts'];

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
        $date_labels[] = date( 'Y-m-d', strtotime( "-{$i} days" ) );
    }
    
    // For each recent post, build views array per day
    foreach ( $recent_post_ids as $pid ) {
        $post_views_by_day[ $pid ] = array();
        
        // Initialize all days to 0
        for ( $i = 0; $i < count( $date_labels ); $i++ ) {
            $post_views_by_day[ $pid ][ $i ] = 0;
        }
        
        // Simulated views data - in a real implementation, you'd get this from analytics
        // Generate random views for demonstration (replace with actual analytics data)
        for ( $i = 0; $i < count( $date_labels ); $i++ ) {
            $simulated_views = rand( 0, 25 );
            $post_views_by_day[ $pid ][ $i ] = $simulated_views;
            
            if ( $simulated_views > $max_views_per_cell ) {
                $max_views_per_cell = $simulated_views;
            }
        }
    }
}
?>

<div class="aistma-heatmap-calendar-wrapper">
	
	<?php if ( $show_widget_promotion ) : ?>
	<div class="aistma-widget-promotion">
		<h3><?php esc_html_e( '🚀 New: Dashboard Widgets Available!', 'ai-story-maker' ); ?></h3>
		<p><?php esc_html_e( 'The analytics below are now available as individual dashboard widgets for better organization and performance.', 'ai-story-maker' ); ?></p>
		<div class="widget-promotion-actions">
			<a href="<?php echo esc_url( admin_url( 'index.php' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View Dashboard Widgets', 'ai-story-maker' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aistma-settings' ) ); ?>" class="button">
				<?php esc_html_e( 'Configure Widgets', 'ai-story-maker' ); ?>
			</a>
			<button type="button" class="button" onclick="dismissWidgetPromotion()" style="margin-left: 10px;">
				<?php esc_html_e( 'Dismiss', 'ai-story-maker' ); ?>
			</button>
		</div>
		<div class="widget-promotion-features">
			<div class="feature-item">
				<span class="dashicons dashicons-chart-pie"></span>
				<strong><?php esc_html_e( 'Data Cards', 'ai-story-maker' ); ?></strong>
				<p><?php esc_html_e( 'Key metrics at a glance', 'ai-story-maker' ); ?></p>
			</div>
			<div class="feature-item">
				<span class="dashicons dashicons-calendar-alt"></span>
				<strong><?php esc_html_e( 'Story Calendar', 'ai-story-maker' ); ?></strong>
				<p><?php esc_html_e( '6-month activity heatmap', 'ai-story-maker' ); ?></p>
			</div>
			<div class="feature-item">
				<span class="dashicons dashicons-admin-post"></span>
				<strong><?php esc_html_e( 'Posts Activity', 'ai-story-maker' ); ?></strong>
				<p><?php esc_html_e( 'Recent posts engagement', 'ai-story-maker' ); ?></p>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="aistma-heatmap-section">
		<div class="aistma-heatmap-header">
			<h3><?php esc_html_e('Story Activity Overview', 'ai-story-maker'); ?></h3>
			
			<div class="aistma-debug-cards">
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
			</div>
		</div>

		<!-- Story Generation Calendar Heatmap -->
		<div class="aistma-heatmap-calendar">
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
					<div class="aistma-heatmap-weekdays-vertical">
						<div class="weekday-vertical"><?php esc_html_e('Mon', 'ai-story-maker'); ?></div>
						<div class="weekday-vertical"></div>
						<div class="weekday-vertical"><?php esc_html_e('Wed', 'ai-story-maker'); ?></div>
						<div class="weekday-vertical"></div>
						<div class="weekday-vertical"><?php esc_html_e('Fri', 'ai-story-maker'); ?></div>
						<div class="weekday-vertical"></div>
						<div class="weekday-vertical"><?php esc_html_e('Sun', 'ai-story-maker'); ?></div>
					</div>
					
					<div class="aistma-heatmap-weeks-vertical">
						<?php foreach ($calendar_data as $week_num => $week_data): ?>
							<div class="aistma-heatmap-week-vertical">
								<?php foreach ($week_data as $day_index => $story_count): ?>
									<?php 
									$intensity_class = 'intensity-0';
									if ($story_count > 0) {
										if ($story_count <= 2) $intensity_class = 'intensity-1';
										elseif ($story_count <= 5) $intensity_class = 'intensity-2';
										elseif ($story_count <= 10) $intensity_class = 'intensity-3';
										else $intensity_class = 'intensity-4';
									}
									?>
									<div class="aistma-heatmap-day <?php echo esc_attr($intensity_class); ?>" 
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

		<!-- Recent Posts x Days Heatmap -->
        <div class="aistma-heatmap-calendar" style="margin-top:20px;">
			<h4><?php esc_html_e('Recent Posts Activity (last 14 days)', 'ai-story-maker'); ?></h4>
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
		</div>

		<!-- Debug Information (visible to admins only) -->
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<div class="aistma-debug-info">
				<h5><?php esc_html_e('Debug Information', 'ai-story-maker'); ?></h5>
				<div class="aistma-debug-recent">
					<strong><?php esc_html_e('Recent Posts:', 'ai-story-maker'); ?></strong><br>
					<?php if ( ! empty( $debug_info['recent_posts'] ) ) : ?>
						<?php foreach ( $debug_info['recent_posts'] as $rp ) : ?>
							<a href="<?php echo esc_url( get_permalink( $rp->ID ) ); ?>" target="_blank">
								<?php echo esc_html( wp_html_excerpt( $rp->post_title ?: __('(no title)', 'ai-story-maker'), 50, '…' ) ); ?>
							</a>
							<small>(<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $rp->post_date ) ) ); ?>)</small><br>
						<?php endforeach; ?>
					<?php else : ?>
						<?php esc_html_e('No recent posts found.', 'ai-story-maker'); ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
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

	// Widget promotion functionality
	function dismissWidgetPromotion() {
		const promotion = document.querySelector('.aistma-widget-promotion');
		if (promotion) {
			promotion.style.transition = 'opacity 0.3s ease';
			promotion.style.opacity = '0';
			setTimeout(() => {
				promotion.style.display = 'none';
			}, 300);
			
			// Save dismissal preference (you can implement this with AJAX)
			localStorage.setItem('aistma_widget_promotion_dismissed', 'true');
		}
	}

	// Check if promotion was previously dismissed
	if (localStorage.getItem('aistma_widget_promotion_dismissed') === 'true') {
		const promotion = document.querySelector('.aistma-widget-promotion');
		if (promotion) {
			promotion.style.display = 'none';
		}
	}
});
</script>

<style>
.aistma-widget-promotion {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 20px;
	border-radius: 12px;
	margin-bottom: 25px;
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
	position: relative;
	overflow: hidden;
}

.aistma-widget-promotion::before {
	content: '';
	position: absolute;
	top: -50%;
	left: -50%;
	width: 200%;
	height: 200%;
	background: repeating-linear-gradient(
		45deg,
		transparent,
		transparent 10px,
		rgba(255,255,255,0.05) 10px,
		rgba(255,255,255,0.05) 20px
	);
	animation: shimmer 3s linear infinite;
	pointer-events: none;
}

@keyframes shimmer {
	0% { transform: translateX(-100%) translateY(-100%); }
	100% { transform: translateX(100%) translateY(100%); }
}

.aistma-widget-promotion h3 {
	margin: 0 0 10px 0;
	font-size: 18px;
	font-weight: 600;
}

.aistma-widget-promotion p {
	margin: 0 0 15px 0;
	opacity: 0.9;
	line-height: 1.5;
}

.widget-promotion-actions {
	margin-bottom: 20px;
}

.aistma-widget-promotion .button {
	margin-right: 8px;
	margin-bottom: 5px;
	border: 1px solid rgba(255,255,255,0.3);
	text-shadow: none;
}

.aistma-widget-promotion .button-primary {
	background: rgba(255,255,255,0.2);
	border-color: rgba(255,255,255,0.4);
	color: white;
}

.aistma-widget-promotion .button-primary:hover {
	background: rgba(255,255,255,0.3);
	border-color: rgba(255,255,255,0.6);
}

.aistma-widget-promotion .button:not(.button-primary) {
	background: transparent;
	color: white;
}

.aistma-widget-promotion .button:not(.button-primary):hover {
	background: rgba(255,255,255,0.1);
}

.widget-promotion-features {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	margin-top: 20px;
}

.widget-promotion-features .feature-item {
	background: rgba(255,255,255,0.1);
	padding: 15px;
	border-radius: 8px;
	text-align: center;
	backdrop-filter: blur(10px);
}

.widget-promotion-features .feature-item .dashicons {
	font-size: 24px;
	width: 24px;
	height: 24px;
	margin-bottom: 8px;
	opacity: 0.9;
}

.widget-promotion-features .feature-item strong {
	display: block;
	margin-bottom: 5px;
	font-size: 14px;
}

.widget-promotion-features .feature-item p {
	margin: 0;
	font-size: 12px;
	opacity: 0.8;
}

@media (max-width: 768px) {
	.widget-promotion-features {
		grid-template-columns: 1fr;
	}
	
	.widget-promotion-actions {
		text-align: center;
	}
	
	.aistma-widget-promotion .button {
		display: block;
		margin: 5px auto;
		width: 100%;
		max-width: 200px;
	}
}
</style>
