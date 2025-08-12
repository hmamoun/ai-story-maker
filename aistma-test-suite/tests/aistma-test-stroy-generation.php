<?php
/**
 * Live Story Generation Test (Active Prompts)
 *
 * Triggers real story generation for all currently ACTIVE prompts and verifies
 * that posts were created with excerpt, tags, and photos (featured image or inline <img>).
 *
 * Requirements:
 * - Valid subscription (Master API). Live calls are made (no HTTP mocking).
 * - Active prompts configured in option `aistma_prompts`.
 *
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

class AISTMA_Test_Story_Generation extends AISTMA_Test_Base {
    /**
     * Test name
     */
    protected $test_name = 'AI Story Generation (live, active prompts)';

    /**
     * Test description
     */
    protected $test_description = 'Runs live generation for active prompts and asserts posts have excerpt, tags, and photos.';

    /**
     * Test category
     */
    protected $test_category = 'Generation';

    /**
     * Run the test
     */
    public function run_test() {
        $this->check_wordpress_loaded();
        $this->check_aistma_active();

        // Verify required classes exist
        $this->check_class_exists('exedotcom\\aistorymaker\\AISTMA_Story_Generator');

        $generator = new \exedotcom\aistorymaker\AISTMA_Story_Generator();
        
        // Ensure we have a valid subscription; otherwise this live test cannot proceed safely
        $subscription = $generator->aistma_get_subscription_status();
        if (empty($subscription['valid'])) {
            throw new Exception('Live generation requires an active subscription (Master API). Please configure `AISTMA_MASTER_URL` and ensure subscription is valid.');
        }

        // Load current prompts and ensure there are active ones
        $raw_settings = get_option('aistma_prompts', '');
        $settings = $raw_settings ? json_decode($raw_settings, true) : array();
        if (json_last_error() !== JSON_ERROR_NONE || empty($settings['prompts']) || !is_array($settings['prompts'])) {
            throw new Exception('Prompts setting (`aistma_prompts`) is missing or malformed.');
        }
        $active_prompts = array_values(array_filter($settings['prompts'], function($p){
            if (!is_array($p)) return false;
            if (empty($p['text']) || empty($p['prompt_id'])) return false;
            return !isset($p['active']) || (int)$p['active'] !== 0;
        }));
        if (empty($active_prompts)) {
            throw new Exception('No active prompts found. Activate at least one prompt in settings.');
        }

        $created_post_ids = array();
        try {
            // Start marker to filter posts created by this run
            $started_at = current_time('timestamp', true); // GMT timestamp

            $this->log_info('Triggering live story generation for active prompts (' . count($active_prompts) . ').');
            \exedotcom\aistorymaker\AISTMA_Story_Generator::generate_ai_stories_with_lock(true);

            // Collect posts created after start marker that have AI meta
            $query_args = array(
                'post_type'      => 'post',
                'post_status'    => array('draft','publish'),
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'date_query'     => array(
                    array(
                        'after'     => gmdate('Y-m-d H:i:s', $started_at - 5),
                        'inclusive' => true,
                        'column'    => 'post_date_gmt',
                    ),
                ),
                'meta_query'     => array(
                    array(
                        'key'     => 'ai_story_maker_request_id',
                        'compare' => 'EXISTS',
                    ),
                ),
            );
            $posts = get_posts($query_args);
            if (empty($posts)) {
                throw new Exception('No AI-generated posts were created. Ensure prompts are active and subscription has credits.');
            }

            $validated = 0;
            foreach ($posts as $p) {
                $pid = (int) $p->ID;
                $created_post_ids[] = $pid;

                // Excerpt must exist
                if (empty(trim($p->post_excerpt))) {
                    throw new Exception('Post ID ' . $pid . ' has empty excerpt.');
                }

                // Tags must exist (Master API path adds tags)
                $tag_names = wp_get_post_tags($pid, array('fields' => 'names'));
                if (empty($tag_names)) {
                    throw new Exception('Post ID ' . $pid . ' has no tags.');
                }

                // Photo: featured image or inline <img>
                $has_thumb = function_exists('has_post_thumbnail') ? has_post_thumbnail($pid) : false;
                $has_img_in_content = (bool) preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $p->post_content);
                if (!$has_thumb && !$has_img_in_content) {
                    throw new Exception('Post ID ' . $pid . ' has no featured image or inline image.');
                }

                $validated++;
            }

            $this->log_info('✅ Validated ' . $validated . ' AI-generated post(s).');
            return 'Validated ' . $validated . ' AI-generated post(s) with excerpt, tags, and photos.';

        } finally {
            // Intentionally keep generated posts for inspection; no cleanup here.
        }
    }
}