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

?>
<div class="wrap">
	<div class="aistma-style-settings">
	<h2>AI Story Maker</h2>
<p>
	AI Story Maker utilizes Generative AI Models to automatically create engaging stories for your WordPress site, adding content based on the topics you choose, which results in better SEO ranking. Getting started is easy — simply enter your API keys and set up your prompts.
</p>
<ul>
	<li>
		<strong>AI Writer:</strong> Offers flexibility to select a subscription plan or integrate your own API keys for personalized story generation.
	</li>
	<li>
		<strong>Settings:</strong> Manage your scheduling preferences, author details, and attribution settings with ease.
	</li>
	<li>
		<strong>Prompts:</strong> Create and manage your prompts and general instructions to tailor story generation to your needs.
	</li>

	<li>
		<strong>Analytics:</strong>
		<ul class="aistma-sub-list">
			<li><strong>Data Cards:</strong> Quick snapshot of stories, views, CTR, and top tags to spot what resonates, so you can reinforce winning topics and hooks in your prompts and retire low performers.</li>
			<li><strong>Story Generation Calendar Heatmap:</strong> Shows activity and engagement by day to reveal best publishing windows and dry spells, helping you adjust prompt cadence, timing, and length.</li>
			<li><strong>Recent Activity & Clicks:</strong> Highlights headlines and openings that get clicks, guiding you to refine the first lines, titles, and calls-to-action in your prompts.</li>
			<li><strong>Activity by Tag:</strong> Compares topics to uncover audience interests, so you can target high-intent tags, sharpen wording/keywords, and phase out weak themes in prompts.</li>
		</ul>
	</li>

	<li><strong>Log:</strong> where you can view the logs of your AI story generation.</li>
	<li>
		<ul>
			<li>
				<strong>Shortcodes:</strong>
				<p>
					<code>[aistma_posts_gadget]</code>: Add a fast, search-friendly posts section that improves internal linking, increases time-on-page, and helps visitors discover more of your content.
				</p>
				<p>
					<strong>Common options:</strong>
					<ul class="aistma-sub-list">
						<li><strong>posts_per_page:</strong> number of posts (default: 6)</li>
						<li><strong>layout:</strong> grid or list</li>
						<li><strong>show_search:</strong> true/false</li>
						<li><strong>show_filters:</strong> true/false</li>
						<li><strong>categories:</strong> comma-separated category IDs (e.g., 2,5)</li>
						<li><strong>date_range:</strong> today, week, month, year</li>
						<li><strong>highlight_new:</strong> true/false (uses new_post_days)</li>
					</ul>
				</p>
				<p>
					<strong>Examples:</strong>
					<ul class="aistma-sub-list">
						<li><code>[aistma_posts_gadget posts_per_page="8" layout="grid" show_search="true"]</code></li>
						<li><code>[aistma_posts_gadget categories="3,7" date_range="month" highlight_new="true"]</code></li>
					</ul>
				</p>
			</li>
			<li>
			
				<p><code>[aistma_scroller]</code>: Displays a sticky, auto‑scrolling story bar at the bottom of the screen with your latest AI‑generated stories. Add it to any page to enable the scroller for that page.	
			</p>
			</li>
		</ul>
	</li>
</ul>
<p>
	Generated stories are saved as WordPress posts. You can display them using the custom template included with the plugin or by embedding the shortcode into any page or post.
</p>

<?php
$plugin_data = get_plugin_data( AISTMA_PATH . 'ai-story-maker.php' );
$version = $plugin_data['Version'];
?>




	
	<?php // Generation controls moved to a reusable template included globally. ?>
	</div>

</div>
