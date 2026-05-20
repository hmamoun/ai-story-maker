<?php
/**
 * Shortcodes Tab Template for AI Story Maker.
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

?>
<div class="wrap">
	<div class="aistma-style-settings">
		<h2><?php esc_html_e( 'Shortcodes', 'ai-story-maker' ); ?></h2>
		<p><?php esc_html_e( 'Use these shortcodes to display AI-generated content on your website.', 'ai-story-maker' ); ?></p>

		<div class="aistma-shortcode-section">
			<h3>Posts Gadget: <code>[aistma_posts_gadget]</code></h3>
			<p><?php esc_html_e( 'Add a fast, search-friendly posts section that improves internal linking, increases time-on-page, and helps visitors discover more of your content.', 'ai-story-maker' ); ?></p>
			
			<h4><?php esc_html_e( 'Common Options:', 'ai-story-maker' ); ?></h4>
			<ul class="aistma-sub-list">
				<li><strong>posts_per_page:</strong> <?php esc_html_e( 'number of posts (default: 6)', 'ai-story-maker' ); ?></li>
				<li><strong>layout:</strong> <?php esc_html_e( 'grid or list', 'ai-story-maker' ); ?></li>
				<li><strong>show_search:</strong> <?php esc_html_e( 'true/false', 'ai-story-maker' ); ?></li>
				<li><strong>show_filters:</strong> <?php esc_html_e( 'true/false', 'ai-story-maker' ); ?></li>
				<li><strong>categories:</strong> <?php esc_html_e( 'comma-separated category IDs (e.g., 2,5)', 'ai-story-maker' ); ?></li>
				<li><strong>date_range:</strong> <?php esc_html_e( 'today, week, month, year', 'ai-story-maker' ); ?></li>
				<li><strong>highlight_new:</strong> <?php esc_html_e( 'true/false (uses new_post_days)', 'ai-story-maker' ); ?></li>
			</ul>

			<h4><?php esc_html_e( 'Examples:', 'ai-story-maker' ); ?></h4>
			<ul class="aistma-sub-list">
				<li><code>[aistma_posts_gadget posts_per_page="8" layout="grid" show_search="true"]</code></li>
				<li><code>[aistma_posts_gadget categories="3,7" date_range="month" highlight_new="true"]</code></li>
			</ul>
		</div>

		<div class="aistma-shortcode-section">
			<h3><?php esc_html_e( 'Analytics dashboard widgets', 'ai-story-maker' ); ?></h3>
			<p><?php esc_html_e( 'Show the same analytics widgets from your WordPress dashboard on any public page, post, or widget area. Useful for author dashboards, member-only stats pages, or internal reporting.', 'ai-story-maker' ); ?></p>

			<h4><?php esc_html_e( 'Data Overview:', 'ai-story-maker' ); ?> <code>[aistma_data_overview]</code></h4>
			<p><?php esc_html_e( 'Displays key publishing metrics in a card layout: total posts, AI-generated post count, stories over the last six months, posts published this month, and a quick list of your latest posts.', 'ai-story-maker' ); ?></p>
			<ul class="aistma-sub-list">
				<li><code>[aistma_data_overview]</code></li>
				<li><code>[aistma_data_overview viewable_by="logged_in"]</code></li>
			</ul>

			<h4><?php esc_html_e( 'Generation Calendar:', 'ai-story-maker' ); ?> <code>[aistma_generation_calendar]</code></h4>
			<p><?php esc_html_e( 'Shows a GitHub-style heatmap of how many AI stories were generated each day over the last three months. Darker squares mean more stories on that day.', 'ai-story-maker' ); ?></p>
			<ul class="aistma-sub-list">
				<li><code>[aistma_generation_calendar]</code></li>
				<li><code>[aistma_generation_calendar viewable_by="admin"]</code></li>
			</ul>

			<h4><?php esc_html_e( 'Recent Activity:', 'ai-story-maker' ); ?> <code>[aistma_recent_activity]</code></h4>
			<p><?php esc_html_e( 'Shows a heatmap of page views for your most recent posts over the last 14 days (based on AI Story Maker traffic logging). Each row is a post; each column is a day.', 'ai-story-maker' ); ?></p>
			<ul class="aistma-sub-list">
				<li><code>[aistma_recent_activity]</code></li>
				<li><code>[aistma_recent_activity viewable_by="logged_in"]</code></li>
			</ul>

			<h4><?php esc_html_e( 'Visibility option (all three widgets)', 'ai-story-maker' ); ?></h4>
			<p><?php esc_html_e( 'Use the viewable_by attribute to control who can see the widget. If the viewer is not allowed, nothing is output.', 'ai-story-maker' ); ?></p>
			<ul class="aistma-sub-list">
				<li><strong>viewable_by="public"</strong> — <?php esc_html_e( 'everyone (default)', 'ai-story-maker' ); ?></li>
				<li><strong>viewable_by="logged_in"</strong> — <?php esc_html_e( 'registered users only', 'ai-story-maker' ); ?></li>
				<li><strong>viewable_by="admin"</strong> — <?php esc_html_e( 'site administrators only', 'ai-story-maker' ); ?></li>
			</ul>
		</div>

		<div class="aistma-shortcode-section">
			<h3>News Scroller: <code>[aistma_scroller]</code></h3>
			<p><?php esc_html_e( 'Displays a sticky, auto‑scrolling story bar at the bottom of the screen with your latest AI‑generated stories. Add it to any page to enable the scroller for that page.', 'ai-story-maker' ); ?></p>
		</div>

		<div class="aistma-shortcode-info">
			<h3><?php esc_html_e( 'How to Use Shortcodes', 'ai-story-maker' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Copy the shortcode you want to use', 'ai-story-maker' ); ?></li>
				<li><?php esc_html_e( 'Paste it into any page, post, or widget', 'ai-story-maker' ); ?></li>
				<li><?php esc_html_e( 'Customize the options by adding parameters in quotes', 'ai-story-maker' ); ?></li>
				<li><?php esc_html_e( 'Save and view your page to see the shortcode in action', 'ai-story-maker' ); ?></li>
			</ol>
		</div>

			</div>
</div>
