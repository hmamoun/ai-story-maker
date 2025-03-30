<?php
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
            <a href="https://exedotcom.ca/ai-story-maker/" target="_blank">AI Story Maker</a>
        </p>

        <?php
        // bmark Schedule to display the next event
        $next_event = wp_next_scheduled( 'ai_story_generator_repeating_event' );
        if ( $next_event ) {
            $time_diff  = $next_event - time();
            $days       = floor( $time_diff / ( 60 * 60 * 24 ) );
            $hours      = floor( ( $time_diff % ( 60 * 60 * 24 ) ) / ( 60 * 60 ) );
            $minutes    = floor( ( $time_diff % ( 60 * 60 ) ) / 60 );
            $next_event = sprintf( '%dd %dh %dm', $days, $hours, $minutes );
            ?>
            <strong>
                A new story will be automatically generated and added to your site in 
                <?php echo esc_html( $next_event ); ?>
            </strong>
        <?php
        } else {
            ?>
            <strong>
                <?php esc_html_e( 'No scheduled story generation.', 'ai-story-maker' ); ?>
            </strong>
        <?php
        }
        ?>
    </div>
</div>
