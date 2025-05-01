<?php
/*
Plugin URI: https://github.com/hmamoun/ai-story-maker/wiki
Description: AI-powered content generator for WordPress — create engaging stories with a single click.
Version: 0.1.0
Author: Hayan Mamoun
Author URI: https://exedotcom.ca
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ai-story-maker
Domain Path: /languages
Requires PHP: 7.4
Requires at least: 5.8
Tested up to: 6.7
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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

<h3>Future Enhancements</h3>
<p>
    This is version 1.0. Future updates will bring support for additional AI models like Gemini, Grok, and DeepSeek,
    along with enhanced options for embedding premium-quality images from various sources.
</p>

<p>
    <strong>For more information, visit:</strong> 
    <a href="https://github.com/hmamoun/ai-story-maker/wiki" target="_blank">AI Story Maker Wiki</a>
</p>


        <?php
 $next_event = wp_next_scheduled( 'aistma_generate_story_event');
 $is_generating = get_transient( 'aistma_generating_lock' );
 
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
                <span style="color: #d98500;"><strong>Currently generating stories... Please recheck in 10 minutes.</strong></span>
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
