<?php
/**
 * Plugin Name: AI Story Maker
 * welcome-tab-template.php
 * included in the admin area for the welcome tab.
 * Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
 * Description: AI-powered WordPress plugin that generates engaging stories, articles, and images using Large Language Models.
 * Version: 1.0
 * Author: Hayan Mamoun
 * Author URI: https://exedotcom.ca
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-story-maker
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.7
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <div class="ai-storymaker-settings">
        <h2>AI Story Maker</h2>
        <p>
            AI Story Maker uses OpenAI's LLMs to automatically create engaging stories for your WordPress site.
            Setting it up is simple&mdash;just enter your API key and add your prompts.
        </p>

        <h3>Getting Started</h3>
        <ul>
            <li>
                <strong>Settings:</strong> Enter your API keys and adjust plugin options.
            </li>
            <li>
                <strong>Prompts:</strong> Go to the Prompts tab to add your prompts and start generating stories.
            </li>
            <li>
                <strong>Shortcode:</strong> Use the <code>[story_scroller]</code> shortcode in your posts or pages to display your stories.
            </li>
        </ul>
        <p>
            Your generated stories are saved as posts and can be shown using our custom template or via the shortcode.
        </p>

        <h3>Easy to Use</h3>
        <p>
            We've built AI Story Maker for simplicity, so even beginners can quickly start generating stories without any hassle.
        </p>

        <h3>Future Enhancements</h3>
        <p>
            This is version 1.0. In future updates, you'll see support for more LLM models (like Gemini, Grok, and Deep Seek)
            and additional options for embedding high-quality photos.
        </p>

        <p>
            <strong>For more information</strong>, visit: 
            <a href="https://github.com/hmamoun/ai-story-maker/wiki" target="_blank">AI Story Maker</a>
        </p>

        <?php
 $next_event = wp_next_scheduled( 'aistima_generate_story_event');
 $is_generating = get_transient( 'ai_story_generator_running' );
 
 if ( $next_event ) {
     $time_diff  = $next_event - time();
     $days       = floor( $time_diff / ( 60 * 60 * 24 ) );
     $hours      = floor( ( $time_diff % ( 60 * 60 * 24 ) ) / ( 60 * 60 ) );
     $minutes    = floor( ( $time_diff % ( 60 * 60 ) ) / 60 );
 
     $formatted_countdown = sprintf( '%dd %dh %dm', $days, $hours, $minutes );
     $formatted_datetime  = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_event );
 
     ?>
     <div class="notice notice-info">
         <strong>
             🕒 Next AI story generation scheduled in <?php echo esc_html( $formatted_countdown ); ?><br>
             📅 Scheduled for: <em><?php echo esc_html( $formatted_datetime ); ?></em><br>
             <?php if ( $is_generating ) : ?>
                 🔄 <span style="color: #d98500;"><strong>Currently generating stories...</strong></span>
             <?php else : ?>
                 ✅ <span style="color: #2f855a;">No generation in progress.</span>
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
