<?php
/**
 * FAQ Tab Template for AI Story Maker.
 *
 * @package AI_Story_Maker
 * @author  Hayan Mamoun
 * @license GPLv2 or later
 * @link    https://github.com/hmamoun/ai-story-maker/wiki
 * @since   2.1.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<div class="aistma-style-settings">
		<h2><?php esc_html_e( 'Frequently Asked Questions', 'ai-story-maker' ); ?></h2>
		<p><?php esc_html_e( 'Find answers to common questions about AI Story Maker. Can\'t find what you\'re looking for? Check the logs or contact support.', 'ai-story-maker' ); ?></p>

		<div class="aistma-faq-container">
			<div class="aistma-faq-nav">
				<ul>
					<li><a href="#getting-started"><?php esc_html_e( 'Getting Started', 'ai-story-maker' ); ?></a></li>
					<li><a href="#story-generation"><?php esc_html_e( 'Story Generation', 'ai-story-maker' ); ?></a></li>
					<li><a href="#story-enhancer"><?php esc_html_e( 'Story Enhancer', 'ai-story-maker' ); ?></a></li>
					<li><a href="#subscriptions"><?php esc_html_e( 'Subscriptions & API Keys', 'ai-story-maker' ); ?></a></li>
					<li><a href="#shortcodes"><?php esc_html_e( 'Shortcodes & Display', 'ai-story-maker' ); ?></a></li>
					<li><a href="#social-media"><?php esc_html_e( 'Social Media', 'ai-story-maker' ); ?></a></li>
					<li><a href="#analytics"><?php esc_html_e( 'Analytics', 'ai-story-maker' ); ?></a></li>
					<li><a href="#troubleshooting"><?php esc_html_e( 'Troubleshooting', 'ai-story-maker' ); ?></a></li>
					<li><a href="#advanced"><?php esc_html_e( 'Advanced Usage', 'ai-story-maker' ); ?></a></li>
				</ul>
			</div>

			<div class="aistma-faq-content">
				<!-- Getting Started -->
				<section id="getting-started" class="aistma-faq-section">
					<h3><?php esc_html_e( 'Getting Started', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What is AI Story Maker?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'AI Story Maker is a powerful WordPress plugin that uses artificial intelligence (OpenAI) to automatically generate high-quality stories and blog posts. It can also enhance existing content, automatically add images from Unsplash, and publish to social media platforms.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What are the system requirements?', 'ai-story-maker' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'WordPress 5.8 or higher', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'PHP 7.4 or higher', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Modern web browser with JavaScript enabled', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Active internet connection (for AI generation and image fetching)', 'ai-story-maker' ); ?></li>
						</ul>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How do I install AI Story Maker?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'You can install it in two ways:', 'ai-story-maker' ); ?></p>
						<ol>
							<li><strong><?php esc_html_e( 'WordPress Plugin Directory', 'ai-story-maker' ); ?>:</strong> <?php esc_html_e( 'Go to Plugins > Add New, search for "AI Story Maker", and click Install and Activate', 'ai-story-maker' ); ?></li>
							<li><strong><?php esc_html_e( 'Manual Upload', 'ai-story-maker' ); ?>:</strong> <?php esc_html_e( 'Download the ZIP file, go to Plugins > Add New > Upload Plugin, select the ZIP file, and activate', 'ai-story-maker' ); ?></li>
						</ol>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Is AI Story Maker free?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'The plugin itself is free and open-source (GPLv2 license). However, to generate stories, you need either:', 'ai-story-maker' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'A subscription plan (which includes API access)', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Your own OpenAI and Unsplash API keys', 'ai-story-maker' ); ?></li>
						</ul>
						<p><strong><?php esc_html_e( 'Note:', 'ai-story-maker' ); ?></strong> <?php esc_html_e( 'The AI Story Enhancer feature is completely free to use with no credits required.', 'ai-story-maker' ); ?></p>
					</div>
				</section>

				<!-- Story Generation -->
				<section id="story-generation" class="aistma-faq-section">
					<h3><?php esc_html_e( 'AI Story Generation', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How does AI story generation work?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'The plugin uses OpenAI\'s API to generate content based on prompts you create. You define the story topic, writing style, length, categories, tags, and image placeholders for Unsplash integration.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What is a prompt template?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'A prompt template is a reusable instruction set that tells the AI how to generate content. It includes the main prompt/instructions, categories to assign, auto-publish settings, and image placeholders (using {img_unsplash:keywords} syntax).', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How do I create effective prompts?', 'ai-story-maker' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'Be specific about the topic and style', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Include desired length (e.g., "Write a 500-word article about...")', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Specify the target audience', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Add tone instructions (e.g., "Write in a friendly, conversational tone")', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Use image placeholders: {img_unsplash:mountain landscape}', 'ai-story-maker' ); ?></li>
						</ul>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Can I schedule automatic story generation?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Yes! You can set up automatic generation schedules in the Generation Controls section. Stories can be generated daily at specific times, weekly on specific days, or based on custom intervals.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How long does it take to generate a story?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Typically 10-30 seconds per story, depending on story length, OpenAI API response time, number of images being fetched, and your server\'s connection speed.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Can I edit generated stories before publishing?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Absolutely! All generated stories are created as WordPress draft posts. You can edit the content, use AI Story Enhancer to improve sections, add or remove images, modify categories and tags, and schedule publication.', 'ai-story-maker' ); ?></p>
					</div>
				</section>

				<!-- Story Enhancer -->
				<section id="story-enhancer" class="aistma-faq-section">
					<h3><?php esc_html_e( 'AI Story Enhancer', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What is AI Story Enhancer?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'AI Story Enhancer is a free feature that lets you improve any existing post by selecting text and asking AI to enhance it. It\'s completely free with no credits required.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How do I access AI Story Enhancer?', 'ai-story-maker' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'Go to Posts > All Posts in WordPress', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Find any post and look for the "AI Story Enhancer" link under the post title', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Click it to open the enhancement interface', 'ai-story-maker' ); ?></li>
						</ol>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How does text enhancement work?', 'ai-story-maker' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'Open AI Story Enhancer for a post', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Select any text in the content preview', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Enter instructions (e.g., "Make this more engaging" or "Add more details")', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Click "Improve" to see AI-powered enhancements', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Review and accept or modify the suggestions', 'ai-story-maker' ); ?></li>
						</ol>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Is AI Story Enhancer free?', 'ai-story-maker' ); ?></h4>
						<p><strong><?php esc_html_e( 'Yes!', 'ai-story-maker' ); ?></strong> <?php esc_html_e( 'AI Story Enhancer is completely free to use with no subscription or credits required.', 'ai-story-maker' ); ?></p>
					</div>
				</section>

				<!-- Subscriptions & API Keys -->
				<section id="subscriptions" class="aistma-faq-section">
					<h3><?php esc_html_e( 'Subscriptions & API Keys', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What\'s the difference between subscription and API keys?', 'ai-story-maker' ); ?></h4>
						<ul>
							<li><strong><?php esc_html_e( 'Subscription', 'ai-story-maker' ); ?>:</strong> <?php esc_html_e( 'Pay a monthly fee, get credits included, no need to manage API keys', 'ai-story-maker' ); ?></li>
							<li><strong><?php esc_html_e( 'API Keys', 'ai-story-maker' ); ?>:</strong> <?php esc_html_e( 'Use your own OpenAI and Unsplash API keys, pay directly to those services', 'ai-story-maker' ); ?></li>
						</ul>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How do I set up my own API keys?', 'ai-story-maker' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'Go to AI Story Maker > Accounts', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Get your OpenAI API key from', 'ai-story-maker' ); ?> <a href="https://platform.openai.com/api-keys" target="_blank">https://platform.openai.com/api-keys</a></li>
							<li><?php esc_html_e( 'Get your Unsplash API key from', 'ai-story-maker' ); ?> <a href="https://unsplash.com/developers" target="_blank">https://unsplash.com/developers</a></li>
							<li><?php esc_html_e( 'Enter both keys in the settings', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Save and test the connection', 'ai-story-maker' ); ?></li>
						</ol>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Can I switch between subscription and API keys?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Yes! You can use either method. If you have both, subscription takes priority for story generation, and your API keys serve as a backup. AI Story Enhancer always works regardless.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How do I check my credit balance?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Go to AI Story Maker > Accounts to see your current credit balance, subscription status, usage history, and renewal date.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Are my API keys secure?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Yes. API keys are stored securely in your WordPress database and are never exposed in the frontend or shared with third parties (except OpenAI/Unsplash when making API calls).', 'ai-story-maker' ); ?></p>
					</div>
				</section>

				<!-- Shortcodes & Display -->
				<section id="shortcodes" class="aistma-faq-section">
					<h3><?php esc_html_e( 'Shortcodes & Display', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What shortcodes are available?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'The plugin provides three main shortcodes:', 'ai-story-maker' ); ?></p>
						<ul>
							<li><code>[aistma_posts_gadget]</code> - <?php esc_html_e( 'Display posts in a grid or list with search/filter', 'ai-story-maker' ); ?></li>
							<li><code>[aistma_scroller]</code> - <?php esc_html_e( 'Sticky scrolling story bar', 'ai-story-maker' ); ?></li>
							<li><code>[aistma_adsense]</code> - <?php esc_html_e( 'AdSense integration', 'ai-story-maker' ); ?></li>
						</ul>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How do I use the posts gadget shortcode?', 'ai-story-maker' ); ?></h4>
						<p><strong><?php esc_html_e( 'Basic usage:', 'ai-story-maker' ); ?></strong></p>
						<code>[aistma_posts_gadget]</code>
						<p><strong><?php esc_html_e( 'With options:', 'ai-story-maker' ); ?></strong></p>
						<code>[aistma_posts_gadget posts_per_page="8" layout="grid" show_search="true" show_filters="true"]</code>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What options are available for [aistma_posts_gadget]?', 'ai-story-maker' ); ?></h4>
						<ul>
							<li><code>posts_per_page</code> - <?php esc_html_e( 'Number of posts (default: 6)', 'ai-story-maker' ); ?></li>
							<li><code>layout</code> - <?php esc_html_e( '"grid" or "list"', 'ai-story-maker' ); ?></li>
							<li><code>show_search</code> - <?php esc_html_e( '"true" or "false"', 'ai-story-maker' ); ?></li>
							<li><code>show_filters</code> - <?php esc_html_e( '"true" or "false"', 'ai-story-maker' ); ?></li>
							<li><code>categories</code> - <?php esc_html_e( 'Comma-separated category IDs (e.g., "2,5")', 'ai-story-maker' ); ?></li>
							<li><code>date_range</code> - <?php esc_html_e( '"today", "week", "month", or "year"', 'ai-story-maker' ); ?></li>
							<li><code>highlight_new</code> - <?php esc_html_e( '"true" or "false"', 'ai-story-maker' ); ?></li>
						</ul>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Where can I place shortcodes?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Shortcodes work in posts and pages (in the editor), widgets (using Text widget), page builders (Elementor, Gutenberg, etc.), and theme templates (using do_shortcode()).', 'ai-story-maker' ); ?></p>
					</div>
				</section>

				<!-- Social Media -->
				<section id="social-media" class="aistma-faq-section">
					<h3><?php esc_html_e( 'Social Media Integration', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Which social media platforms are supported?', 'ai-story-maker' ); ?></h4>
						<p><strong><?php esc_html_e( 'Currently:', 'ai-story-maker' ); ?></strong></p>
						<ul>
							<li><?php esc_html_e( 'Facebook Pages - Full support', 'ai-story-maker' ); ?></li>
						</ul>
						<p><strong><?php esc_html_e( 'Coming soon:', 'ai-story-maker' ); ?></strong></p>
						<ul>
							<li><?php esc_html_e( 'X (Twitter)', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'LinkedIn Company Pages', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Instagram Business Accounts', 'ai-story-maker' ); ?></li>
						</ul>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How does auto-publishing work?', 'ai-story-maker' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'Enable "Auto-Publish New Stories" in Social Media settings', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Select which accounts should receive auto-published posts', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'When a story is published, it automatically shares to enabled accounts', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Posts include title, excerpt, link, and hashtags', 'ai-story-maker' ); ?></li>
						</ol>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How are hashtags generated?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Hashtags come from:', 'ai-story-maker' ); ?></p>
						<ol>
							<li><?php esc_html_e( 'Post tags (automatically converted: "tech news" → "#technews")', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Default hashtags (set in Social Media settings)', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Custom hashtags (added per post)', 'ai-story-maker' ); ?></li>
						</ol>
					</div>
				</section>

				<!-- Analytics -->
				<section id="analytics" class="aistma-faq-section">
					<h3><?php esc_html_e( 'Analytics & Performance', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What analytics are available?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'The plugin tracks post views and traffic, click-through rates, engagement heatmaps, tag-based performance, and time-based trends.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How do I view analytics?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Go to AI Story Maker > Analytics to see traffic heatmaps, recent post activity, performance metrics, and detailed logs.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'What is a heatmap?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'A heatmap visualizes which posts and tags are getting the most traffic, helping you identify popular content topics.', 'ai-story-maker' ); ?></p>
					</div>
				</section>

				<!-- Troubleshooting -->
				<section id="troubleshooting" class="aistma-faq-section">
					<h3><?php esc_html_e( 'Troubleshooting', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Stories aren\'t generating. What\'s wrong?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Check these common issues:', 'ai-story-maker' ); ?></p>
						<ol>
							<li><?php esc_html_e( 'API Keys: Verify your OpenAI API key is valid', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Subscription: Check if your subscription is active and has credits', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Internet: Ensure your server can reach OpenAI API', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Logs: Check AI Story Maker > Log for specific error messages', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Permissions: Ensure WordPress can create posts', 'ai-story-maker' ); ?></li>
						</ol>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Images aren\'t appearing in stories', 'ai-story-maker' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'Verify Unsplash API key is set (if using API keys)', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Check image placeholder syntax: {img_unsplash:keywords}', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Ensure keywords are descriptive (e.g., "sunset beach" not just "image")', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Check logs for Unsplash API errors', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Verify internet connectivity', 'ai-story-maker' ); ?></li>
						</ol>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Shortcodes aren\'t displaying', 'ai-story-maker' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'Clear WordPress cache', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Clear browser cache', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Verify shortcode syntax (check for typos)', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Ensure posts exist to display', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Check if theme supports shortcodes in that location', 'ai-story-maker' ); ?></li>
						</ol>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Social media auto-publish isn\'t working', 'ai-story-maker' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'Auto-publish is enabled in settings', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'At least one account is connected and enabled', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Posts are being published (not saved as drafts)', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Account connections are still valid (re-authenticate if needed)', 'ai-story-maker' ); ?></li>
							<li><?php esc_html_e( 'Check logs for specific error messages', 'ai-story-maker' ); ?></li>
						</ol>
					</div>
				</section>

				<!-- Advanced Usage -->
				<section id="advanced" class="aistma-faq-section">
					<h3><?php esc_html_e( 'Advanced Usage', 'ai-story-maker' ); ?></h3>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Can I generate stories in multiple languages?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Yes! Include language instructions in your prompts. For example: "Write in Spanish..." or "Create content in French...". The AI will generate content in the specified language.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Can developers extend the plugin?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'Absolutely! The plugin is open-source (GPLv2) and includes WordPress action hooks, filter hooks, well-documented code, and an extensible architecture.', 'ai-story-maker' ); ?></p>
					</div>

					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'How do I report bugs or request features?', 'ai-story-maker' ); ?></h4>
						<p><?php esc_html_e( 'You can report issues and request features through:', 'ai-story-maker' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'GitHub Issues:', 'ai-story-maker' ); ?> <a href="https://github.com/hmamoun/ai-story-maker/issues" target="_blank">https://github.com/hmamoun/ai-story-maker/issues</a></li>
							<li><?php esc_html_e( 'Plugin support page', 'ai-story-maker' ); ?></li>
						</ul>
					</div>
				</section>

				<!-- Additional Resources -->
				<section class="aistma-faq-section">
					<h3><?php esc_html_e( 'Additional Resources', 'ai-story-maker' ); ?></h3>
					<div class="aistma-faq-item">
						<h4><?php esc_html_e( 'Where can I learn more?', 'ai-story-maker' ); ?></h4>
						<ul>
							<li><strong><?php esc_html_e( 'Official Website:', 'ai-story-maker' ); ?></strong> <a href="https://www.storymakerplugin.com/" target="_blank">https://www.storymakerplugin.com/</a></li>
							<li><strong><?php esc_html_e( 'GitHub Repository:', 'ai-story-maker' ); ?></strong> <a href="https://github.com/hmamoun/ai-story-maker" target="_blank">https://github.com/hmamoun/ai-story-maker</a></li>
							<li><strong><?php esc_html_e( 'Documentation:', 'ai-story-maker' ); ?></strong> <?php esc_html_e( 'Check the docs folder in the plugin', 'ai-story-maker' ); ?></li>
						</ul>
					</div>
				</section>
			</div>
		</div>
	</div>
</div>

<style>
.aistma-faq-container {
	display: flex;
	gap: 30px;
	margin-top: 20px;
}

.aistma-faq-nav {
	flex: 0 0 200px;
	position: sticky;
	top: 32px;
	align-self: flex-start;
}

.aistma-faq-nav ul {
	list-style: none;
	margin: 0;
	padding: 0;
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 10px;
}

.aistma-faq-nav li {
	margin: 0;
	padding: 0;
}

.aistma-faq-nav a {
	display: block;
	padding: 8px 12px;
	text-decoration: none;
	color: #2271b1;
	border-radius: 3px;
	transition: background 0.2s;
}

.aistma-faq-nav a:hover {
	background: #f0f0f1;
}

.aistma-faq-content {
	flex: 1;
}

.aistma-faq-section {
	margin-bottom: 40px;
	padding-bottom: 30px;
	border-bottom: 1px solid #e5e5e5;
}

.aistma-faq-section:last-child {
	border-bottom: none;
}

.aistma-faq-section h3 {
	font-size: 24px;
	margin-top: 0;
	margin-bottom: 20px;
	color: #1d2327;
	padding-bottom: 10px;
	border-bottom: 2px solid #2271b1;
}

.aistma-faq-item {
	margin-bottom: 30px;
	background: #f9f9f9;
	padding: 20px;
	border-radius: 4px;
	border-left: 4px solid #2271b1;
}

.aistma-faq-item h4 {
	margin-top: 0;
	margin-bottom: 12px;
	color: #2271b1;
	font-size: 18px;
}

.aistma-faq-item p {
	margin-bottom: 10px;
	line-height: 1.6;
}

.aistma-faq-item ul,
.aistma-faq-item ol {
	margin-left: 20px;
	margin-bottom: 10px;
}

.aistma-faq-item code {
	background: #fff;
	padding: 2px 6px;
	border-radius: 3px;
	font-family: Consolas, Monaco, monospace;
	font-size: 14px;
}

.aistma-faq-item a {
	color: #2271b1;
	text-decoration: none;
}

.aistma-faq-item a:hover {
	text-decoration: underline;
}

@media (max-width: 782px) {
	.aistma-faq-container {
		flex-direction: column;
	}

	.aistma-faq-nav {
		position: static;
		flex: none;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Smooth scrolling for FAQ navigation links
	const faqNavLinks = document.querySelectorAll('.aistma-faq-nav a');
	
	faqNavLinks.forEach(function(link) {
		link.addEventListener('click', function(e) {
			const href = this.getAttribute('href');
			if (href.startsWith('#')) {
				e.preventDefault();
				const targetId = href.substring(1);
				const targetElement = document.getElementById(targetId);
				
				if (targetElement) {
					targetElement.scrollIntoView({
						behavior: 'smooth',
						block: 'start'
					});
					
					// Update URL without jumping
					history.pushState(null, null, href);
				}
			}
		});
	});
	
	// Highlight active section on scroll
	const faqSections = document.querySelectorAll('.aistma-faq-section');
	const navLinks = document.querySelectorAll('.aistma-faq-nav a');
	
	function updateActiveNav() {
		let current = '';
		faqSections.forEach(function(section) {
			const sectionTop = section.offsetTop;
			const sectionHeight = section.clientHeight;
			if (window.pageYOffset >= sectionTop - 100) {
				current = section.getAttribute('id');
			}
		});
		
		navLinks.forEach(function(link) {
			link.classList.remove('active');
			if (link.getAttribute('href') === '#' + current) {
				link.classList.add('active');
			}
		});
	}
	
	window.addEventListener('scroll', updateActiveNav);
	updateActiveNav();
});
</script>

<style>
.aistma-faq-nav a.active {
	background: #2271b1;
	color: #fff;
	font-weight: 600;
}
</style>

