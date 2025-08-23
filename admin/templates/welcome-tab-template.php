<?php
/**
 * Welcome Tab Template for AI Story Maker.
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

$story_data = aistma_get_story_generation_data();
$total_stories = array_sum($story_data);
$max_stories_per_day = !empty($story_data) ? max($story_data) : 0;

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

$calendar_data = aistma_generate_calendar_data($story_data);

// Debug information (remove this in production)
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
    // Build date range and labels (last N days ending today)
    $date_index_map = array();
    for ( $i = $aistma_views_days_window - 1; $i >= 0; $i-- ) {
        $date = new DateTime( '-' . $i . ' days', wp_timezone() );
        $label = $date->format( 'Y-m-d' );
        $date_labels[] = $label;
        $date_index_map[ $label ] = count( $date_labels ) - 1;
    }

    // Prepare IN placeholders
    $placeholders = implode( ',', array_fill( 0, count( $recent_post_ids ), '%d' ) );
    $table = $wpdb->prefix . 'aistma_traffic_info';
    $date_from = ( new DateTime( '-' . ( $aistma_views_days_window - 1 ) . ' days', wp_timezone() ) )->format( 'Y-m-d 00:00:00' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT post_id, DATE(viewed_at) AS vdate, COUNT(*) AS views
         FROM {$table}
         WHERE viewed_at >= %s AND post_id IN ($placeholders)
         GROUP BY post_id, DATE(viewed_at)",
         array_merge( array( $date_from ), $recent_post_ids )
    ) );

    // Initialize matrix
    foreach ( $recent_post_ids as $pid ) {
        $post_views_by_day[ $pid ] = array_fill( 0, $aistma_views_days_window, 0 );
    }

    foreach ( (array) $rows as $r ) {
        $pid = (int) $r->post_id;
        $vdate = $r->vdate;
        $views = (int) $r->views;
        if ( isset( $date_index_map[ $vdate ] ) && isset( $post_views_by_day[ $pid ] ) ) {
            $idx = $date_index_map[ $vdate ];
            $post_views_by_day[ $pid ][ $idx ] = $views;
            if ( $views > $max_views_per_cell ) {
                $max_views_per_cell = $views;
            }
        }
    }
}

// Weekly views (last 5 ISO weeks) for full list
$weeks_window = 5;
$week_labels = array();
$week_bounds = array();
for ( $w = $weeks_window - 1; $w >= 0; $w-- ) {
    $start = new DateTime( 'monday -' . $w . ' week', wp_timezone() );
    $end = clone $start; $end->modify( '+6 days' );
    $week_labels[] = $start->format( 'M j' ) . ' - ' . $end->format( 'M j' );
    $week_bounds[] = array( $start->format( 'Y-m-d 00:00:00' ), $end->format( 'Y-m-d 23:59:59' ) );
}

// Fetch weekly counts for posts that have views in the window to keep list smaller
$weekly_counts = array(); // post_id => [w0..wN]
$table = $wpdb->prefix . 'aistma_traffic_info';
$window_start = $week_bounds[0][0];
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$posts_with_views = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$table} WHERE viewed_at >= %s", $window_start ) );
if ( ! empty( $posts_with_views ) ) {
    $placeholders_all = implode( ',', array_fill( 0, count( $posts_with_views ), '%d' ) );
    // Initialize
    foreach ( $posts_with_views as $pid ) {
        $weekly_counts[ (int) $pid ] = array_fill( 0, $weeks_window, 0 );
    }
    // One query per week (keeps SQL simple and index-friendly)
    foreach ( $week_bounds as $wi => $bounds ) {
        list( $ws, $we ) = $bounds;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows_w = $wpdb->get_results( $wpdb->prepare(
            "SELECT post_id, COUNT(*) AS views
             FROM {$table}
             WHERE viewed_at BETWEEN %s AND %s AND post_id IN ($placeholders_all)
             GROUP BY post_id",
             array_merge( array( $ws, $we ), array_map( 'intval', $posts_with_views ) )
        ) );
        foreach ( (array) $rows_w as $rw ) {
            $pid = (int) $rw->post_id;
            $weekly_counts[ $pid ][ $wi ] = (int) $rw->views;
        }
    }
}
?>
<div class="wrap">
	<div class="aistma-style-settings">
	<h2>AI Story Maker</h2>
<p>
	AI Story Maker leverages OpenAI's advanced language models to automatically create engaging stories for your WordPress site.
	Getting started is easy — simply enter your API keys and set up your prompts.
</p>

<h3>Getting Started</h3>
<ul>
	<li>
		<strong>Settings:</strong> Enter your OpenAI and Unsplash API keys and configure your story generation preferences.
	</li>
	<li>
		<strong>Prompts:</strong> Visit the Prompts tab to create and manage the instructions that guide story generation.
	</li>
	<li>
		<strong>Shortcode:</strong> Use the <code>[aistma_scroller]</code> shortcode to display your AI-generated stories anywhere on your site.
	</li>
</ul>
<p>
	Generated stories are saved as WordPress posts. You can display them using the custom template included with the plugin or by embedding the shortcode into any page or post.
</p>

<h3>Easy to Use</h3>
<p>
	AI Story Maker is designed for simplicity and flexibility, making it easy for users of any skill level to start generating rich, AI-driven content within minutes.
</p>
<?php
$plugin_data = get_plugin_data( AISTMA_PATH . 'ai-story-maker.php' );
$version = $plugin_data['Version'];
?>
<h3>Future Enhancements</h3>
<p>
	This is version <?php echo esc_html( 	$version ); ?>. Future updates will bring support for additional AI models like Gemini, Grok, and DeepSeek,
	along with enhanced options for embedding premium-quality images from various sources, for full features list, please visit the <a href="https://exedotcom.ca/ai-story-maker/" target="_blank">AI Story Maker</a> website.
</p>

<!-- Story Generation Heatmap -->
<div class="aistma-heatmap-section">
	<h3><?php esc_html_e('Story Generation Activity', 'ai-story-maker'); ?></h3>
		
	
	<div class="aistma-heatmap-container">
		<div class="aistma-heatmap-header">
			<div class="aistma-heatmap-stats">
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html($total_stories); ?></span>
					<span class="stat-label"><?php esc_html_e('Total Stories', 'ai-story-maker'); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html($max_stories_per_day); ?></span>
					<span class="stat-label"><?php esc_html_e('Max/Day', 'ai-story-maker'); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html(count($story_data)); ?></span>
					<span class="stat-label"><?php esc_html_e('Active Days', 'ai-story-maker'); ?></span>
				</div>
			</div>
		</div>
		
		<div class="aistma-heatmap-calendar">
			<div class="aistma-heatmap-legend">
				<span class="legend-label"><?php esc_html_e('Less', 'ai-story-maker'); ?></span>
				<div class="legend-squares">
					<div class="legend-square intensity-0"></div>
					<div class="legend-square intensity-1"></div>
					<div class="legend-square intensity-2"></div>
					<div class="legend-square intensity-3"></div>
					<div class="legend-square intensity-4"></div>
				</div>
				<span class="legend-label"><?php esc_html_e('More', 'ai-story-maker'); ?></span>
			</div>
			
			<div class="aistma-heatmap-grid">
				<div class="aistma-heatmap-container-vertical">
					<div class="aistma-heatmap-weekdays-vertical">
						<div class="weekday-vertical"><?php esc_html_e('Mon', 'ai-story-maker'); ?></div>
						<div class="weekday-vertical"><?php esc_html_e('Tue', 'ai-story-maker'); ?></div>
						<div class="weekday-vertical"><?php esc_html_e('Wed', 'ai-story-maker'); ?></div>
						<div class="weekday-vertical"><?php esc_html_e('Thu', 'ai-story-maker'); ?></div>
						<div class="weekday-vertical"><?php esc_html_e('Fri', 'ai-story-maker'); ?></div>
						<div class="weekday-vertical"><?php esc_html_e('Sat', 'ai-story-maker'); ?></div>
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

		<!-- Debug Information moved below the heatmap (visible to admins only) -->
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<div class="aistma-debug-info">


				<div class="aistma-debug-recent">
					<strong><?php esc_html_e( 'Recent Posts', 'ai-story-maker' ); ?>:</strong>
					<?php
						$recent_links = array();
						foreach ( $debug_info['recent_posts'] as $recent_post ) {
							$permalink  = get_permalink( $recent_post->ID );
							$title      = $recent_post->post_title ? $recent_post->post_title : __( '(no title)', 'ai-story-maker' );
							$recent_links[] = '<a href="' . esc_url( $permalink ) . '" target="_blank">' . esc_html( $title ) . '</a>';
						}
						echo wp_kses_post( implode( ', ', $recent_links ) );
					?>
				</div>

				<!-- Views by Week (last 5 weeks) -->
				<div class="aistma-views-tabs" style="margin-top:15px;">
					<div class="tabs-header">
						<button class="tab-btn active" data-tab="summary"><?php esc_html_e('Summary', 'ai-story-maker'); ?></button>
						<button class="tab-btn" data-tab="weekly"><?php esc_html_e('Weekly Views (5 weeks)', 'ai-story-maker'); ?></button>
					</div>
					<div class="tabs-body">
						<div class="tab-pane active" id="tab-summary">
							<p><?php esc_html_e('The table below shows total views per post by ISO week for the last 5 weeks.', 'ai-story-maker'); ?></p>
						</div>
						<div class="tab-pane" id="tab-weekly">
							<table class="widefat fixed striped">
								<thead>
									<tr>
										<th><?php esc_html_e('Post', 'ai-story-maker'); ?></th>
										<?php foreach ( $week_labels as $wl ) : ?>
											<th><?php echo esc_html( $wl ); ?></th>
										<?php endforeach; ?>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $weekly_counts ) ) : ?>
										<tr><td colspan="<?php echo esc_attr( 1 + count( $week_labels ) ); ?>"><?php esc_html_e('No views recorded in the selected window.', 'ai-story-maker'); ?></td></tr>
									<?php else : ?>
										<?php foreach ( $weekly_counts as $pid => $wcounts ) : ?>
											<tr>
												<td><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $pid ) ?: __( '(no title)', 'ai-story-maker' ) ); ?></a></td>
												<?php foreach ( $wcounts as $c ) : ?>
													<td><?php echo esc_html( (int) $c ); ?></td>
												<?php endforeach; ?>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
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

	// Simple tabs logic for Weekly Views section
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
});
</script>

<div class="aistma-generate-stories-section">
	<h3><?php esc_html_e( 'Generate Stories Now', 'ai-story-maker' ); ?></h3>
	<p>
		<?php esc_html_e( 'Click the button below to manually generate AI stories using your configured prompts and settings.', 'ai-story-maker' ); ?>
	</p>
	
	<?php
	$is_generating   = get_transient( 'aistma_generating_lock' );
	$button_disabled = $is_generating ? 'disabled' : '';
	$button_text     = $is_generating
		? __( 'Story generation in progress [recheck in 10 minutes]', 'ai-story-maker' )
		: __( 'Generate AI Stories', 'ai-story-maker' );
	?>

	<input type="hidden" id="generate-story-nonce" value="<?php echo esc_attr( wp_create_nonce( 'generate_story_nonce' ) ); ?>">
	<button
		id="aistma-generate-stories-button"
		class="button button-primary"
		<?php echo esc_attr( $button_disabled ); ?>
	>
		<?php echo esc_html( $button_text ); ?>
	</button>
</div>

		<?php
		$next_event    = wp_next_scheduled( 'aistma_generate_story_event' );
		$is_generating = get_transient( 'aistma_generating_lock' );

		if ( $next_event ) {
			$time_diff = $next_event - time();
			$days      = floor( $time_diff / ( 60 * 60 * 24 ) );
			$hours     = floor( ( $time_diff % ( 60 * 60 * 24 ) ) / ( 60 * 60 ) );
			$minutes   = floor( ( $time_diff % ( 60 * 60 ) ) / 60 );

			$formatted_countdown = sprintf( '%dd %dh %dm', $days, $hours, $minutes );
			$formatted_datetime  = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_event );

			?>
	<div class="notice notice-info">
		<strong>
			🕒 Next AI story generation scheduled in <?php echo esc_html( $formatted_countdown ); ?><br>
			📅 Scheduled for: <em><?php echo esc_html( $formatted_datetime ); ?></em><br>
			<?php if ( $is_generating ) : ?>
				<span style="color: #d98500;"><strong>Currently generating stories... Please recheck in 10 minutes.</strong></span>
			<?php endif; ?>
		</strong>
	</div>
			<?php
		} else {
			?>
	<div class="notice notice-warning">
		<strong>
			<?php esc_html_e( 'No scheduled story generation found.', 'ai-story-maker' ); ?>
		</strong>
	</div>
			<?php
		}
		?>
	</div>
</div>
