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
AI Story Maker crafts posts for you using the prompts you’ve saved — instantly with a click, or automatically on a schedule. It’s your hands-free content creator for consistent, engaging stories that boost your site’s visibility.</p>
<h3>Getting Started</h3>
<ul>
	<li>
		<strong>1- <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_AI_WRITER ); ?>">Accounts:</a></strong> Register for a plan <i> or </i> use your API keys [advanced users]
	</li>
	<li>
		<strong>2- <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_PROMPTS ); ?>">Prompts:</a></strong> Create and manage instructions and prompts to guide how your stories are generated.
	</li>
	<li>
		<strong>3- <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_SETTINGS ); ?>">Settings:</a></strong> Schedule, author and attribution settings.
	</li>
</ul>

<div class="aistma-collapsible-section">
	<button type="button" class="aistma-collapsible-toggle" onclick="toggleCollapsibleSection()">
		<span class="aistma-toggle-icon">▼</span> Advanced Features
	</button>
	<div class="aistma-collapsible-content" id="aistma-advanced-features" style="display: none;">
		<ul>
			<li>
				<strong>4- <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_SOCIAL_MEDIA ); ?>">Social Media Integration:</a></strong> Automatically publish your AI-generated stories to Facebook, Twitter/X, LinkedIn, and Instagram. Configure multiple accounts, set up auto-publishing, and track your social media reach.
			</li>

			<li>
				<strong>5- <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_ANALYTICS ); ?>">Analytics:</a></strong>
				<ul class="aistma-sub-list">
					<li><strong>Data Cards:</strong> Quick snapshot of stories, views, CTR, and top tags to spot what resonates, so you can reinforce winning topics and hooks in your prompts and retire low performers.</li>
					<li><strong>Story Generation Calendar Heatmap:</strong> Shows activity and engagement by day to reveal best publishing windows and dry spells, helping you adjust prompt cadence, timing, and length.</li>
					<li><strong>Recent Activity & Clicks:</strong> Highlights headlines and openings that get clicks, guiding you to refine the first lines, titles, and calls-to-action in your prompts.</li>
					<li><strong>Activity by Tag:</strong> Compares topics to uncover audience interests, so you can target high-intent tags, sharpen wording/keywords, and phase out weak themes in prompts.</li>
				</ul>
			</li>

			<li><strong>6- <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_SHORTCODES ); ?>">Shortcodes:</a></strong> Learn how to use shortcodes to display AI-generated content on your website.</li>
			<li><strong>7- <a href="?page=aistma-settings&tab=<?php echo esc_attr( self::TAB_LOG ); ?>">Log:</a></strong> where you can view the logs of your AI story generation.</li>
		</ul>
	</div>
</div>

<style>
.aistma-collapsible-section {
	margin: 15px 0;
}

.aistma-collapsible-toggle {
	background: #f1f1f1;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 10px 15px;
	cursor: pointer;
	width: 100%;
	text-align: left;
	font-weight: 600;
	color: #333;
	transition: background-color 0.3s ease;
}

.aistma-collapsible-toggle:hover {
	background: #e8e8e8;
}

.aistma-toggle-icon {
	display: inline-block;
	margin-right: 8px;
	transition: transform 0.3s ease;
}

.aistma-collapsible-content {
	padding: 15px;
	background: #fafafa;
	border: 1px solid #ddd;
	border-top: none;
	border-radius: 0 0 4px 4px;
}

.aistma-collapsible-content.collapsed .aistma-toggle-icon {
	transform: rotate(-90deg);
}
</style>

<script>
function toggleCollapsibleSection() {
	const content = document.getElementById('aistma-advanced-features');
	const toggle = document.querySelector('.aistma-collapsible-toggle');
	const icon = document.querySelector('.aistma-toggle-icon');
	
	if (content.style.display === 'none') {
		content.style.display = 'block';
		icon.style.transform = 'rotate(0deg)';
		toggle.classList.remove('collapsed');
	} else {
		content.style.display = 'none';
		icon.style.transform = 'rotate(-90deg)';
		toggle.classList.add('collapsed');
	}
}
</script>
<p>
  Want to generate stories? After completing steps 1, 2, and 3 above, head over to your 
  <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">Posts</a> page and click the <strong>“Generate AI Stories"</strong> button.
</p>
<section role="note" aria-label="AI Story Maker reviews">
  <h3>Enjoying AI Story Maker?</h3>
  <p>
    If AI Story Maker helps you craft better stories or saves you time —
    please consider
    <a href="https://wordpress.org/support/plugin/ai-story-maker/reviews/" target="_blank" rel="noopener">
      ⭐ rating it and sharing your feedback
    </a>.
  </p>
  <p>Your reviews and suggestions help shape new features and make the plugin even smarter for everyone.</p>
  <p>Thank you for being part of the journey!</p>
</section><?php
$plugin_data = get_plugin_data( AISTMA_PATH . 'ai-story-maker.php' );
$version = $plugin_data['Version'];
?>




	
	<?php // Generation controls moved to a reusable template included globally. ?>
	</div>

</div>
