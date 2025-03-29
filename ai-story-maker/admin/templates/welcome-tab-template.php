<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Now use the $data array to access your values.
?>
<div class="wrap">
    <div class="ai-storymaker-settings">

        <h2>Welcome to AI Story Maker</h2>
        <p>
            AI Story Maker is a powerful WordPress plugin that uses OpenAI's LLMs to automatically generate captivating stories. With a user-friendly interface, setting up and using the plugin is as simple as entering your API key and starting to create prompts!
        </p>

        <h3>Getting Started</h3>
        <ul>
            <li>
                <strong>Settings:</strong> Navigate to the Settings page to input required API keys and adjust other plugin options.
            </li>
            <li>
                <strong>Prompts:</strong> Head over to the Prompts tab to add your custom prompts and trigger story generation.
            </li>
            <li>
                <strong>Shortcode:</strong> Insert the <code>[story_scroller]</code> shortcode in your posts or pages to display your generated stories in a dynamic scroller format.
            </li>
        </ul>

        <p>
            Your stories are automatically saved as posts on your WordPress site and can be showcased using our custom template or via the shortcode.
        </p>

        <h3>Ease of Use</h3>
        <p>
            We've designed AI Story Maker with simplicity in mind. The straightforward setup ensures that even beginners can start generating engaging stories without any hassle.
        </p>

        <h3>Future Enhancements</h3>
        <p>
            This is version 1.0 of AI Story Maker. In future releases, you can expect support for more advanced LLM models—such as Gemini, Grok, and Deep Seek—as well as additional options for embedding high-quality photos.
        </p>

        <p>
            <strong>For more information</strong>, visit: <a href="https://exedotcom.ca/ai-story-maker/" target="_blank">AI Story Maker</a>
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
            <strong>A new story will be automatically generated and added to your site in <?php echo esc_html( $next_event ); ?></strong>
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
