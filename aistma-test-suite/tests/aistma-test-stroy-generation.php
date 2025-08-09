<?php
/**
 * Story Generation Test (Master API mocked)
 *
 * Verifies that generating a story creates a post with title, excerpt, content, image tag in content, and tags.
 * Uses pre_http_request to mock Master API endpoints so the test is deterministic and offline-capable.
 *
 * @package AISTMA_Test_Suite
 * @since 1.0.0
 */

class AISTMA_Test_Story_Generation extends AISTMA_Test_Base {
    /**
     * Test name
     */
    protected $test_name = 'AI Story Generation (mocked Master API)';

    /**
     * Test description
     */
    protected $test_description = 'Generates a story and asserts title, excerpt, content (with image), and tags are saved to the post.';

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

        // Ensure constants needed by generator are present
        if (!defined('AISTMA_MASTER_URL')) {
            define('AISTMA_MASTER_URL', home_url());
        }

        // Verify required classes exist
        $this->check_class_exists('exedotcom\\aistorymaker\\AISTMA_Story_Generator');

        $generator = new \exedotcom\aistorymaker\AISTMA_Story_Generator();

        // Unique test identifiers
        $unique_suffix = wp_generate_uuid4();
        $expected = array(
            'title'    => 'AISTMA Test Story ' . $unique_suffix,
            'excerpt'  => 'This is a concise summary for the mocked story.',
            'content'  => 'This is the main body for the mocked story. It should be rich and informative.',
            'image_url'=> 'https://via.placeholder.com/640x360.png?text=AISTMA+Test',
            'tags'     => array('AI', 'Healthcare', 'Innovation'),
        );

        $domain = parse_url(home_url(), PHP_URL_HOST) ?: 'localhost';

        // Mock Master API over WP HTTP API
        $mock = function($preempt, $args, $url) use ($domain, $expected) {
            if (strpos($url, '/wp-json/exaig/v1/verify-subscription') !== false) {
                $body = wp_json_encode(array(
                    'valid' => true,
                    'domain' => $domain,
                    'credits_remaining' => 100,
                    'package_id' => 'pkg_test',
                    'package_name' => 'Test Package',
                    'price' => 0,
                    'created_at' => gmdate('c'),
                ));
                return array(
                    'headers'  => array('Content-Type' => 'application/json'),
                    'body'     => $body,
                    'response' => array('code' => 200, 'message' => 'OK'),
                    'cookies'  => array(),
                );
            }

            if (strpos($url, '/wp-json/exaig/v1/generate-story') !== false) {
                $content_html = '<p>' . esc_html($expected['content']) . '</p>'
                    . '<figure><img src="' . esc_url($expected['image_url']) . '" alt="test image" /></figure>';

                $body = wp_json_encode(array(
                    'success' => true,
                    'content' => array(
                        'title' => $expected['title'],
                        'content' => $content_html,
                        'excerpt' => $expected['excerpt'],
                        'references' => array(
                            array('title' => 'Reference 1', 'url' => 'https://example.com')
                        ),
                        'tags' => $expected['tags'],
                    ),
                    'usage' => array(
                        'total_tokens' => 321,
                        'request_id' => 'req_' . wp_generate_uuid4(),
                    ),
                ));

                return array(
                    'headers'  => array('Content-Type' => 'application/json'),
                    'body'     => $body,
                    'response' => array('code' => 200, 'message' => 'OK'),
                    'cookies'  => array(),
                );
            }

            return $preempt;
        };

        add_filter('pre_http_request', $mock, 10, 3);

        $created_post_id = 0;
        try {
            // Minimal prompt/default settings; Master API will be used due to mocked valid subscription
            $prompt = array(
                'prompt_id'    => 'test_' . time(),
                'text'         => 'Write a short article about AI (mocked).',
                'category'     => 'Technology',
                'photos'       => 0,
                'auto_publish' => 0,
            );

            $defaults = array(
                'model' => 'gpt-4-turbo',
                'system_content' => 'You are a professional writer.',
                'timeout' => 15,
            );

            $this->log_info('Triggering story generation via AISTMA_Story_Generator (mocked Master API).');
            $generator->generate_ai_story($prompt['prompt_id'], $prompt, $defaults, 'sk-test', '');

            // Locate the created post by unique title
            $post = get_page_by_title($expected['title'], OBJECT, 'post');
            if (!$post) {
                // Fallback: query latest posts and find a matching slug fragment
                $recent = get_posts(array('numberposts' => 5, 'post_status' => array('draft','publish')));
                foreach ($recent as $p) {
                    if ($p->post_title === $expected['title']) {
                        $post = $p;
                        break;
                    }
                }
            }

            if (!$post) {
                throw new Exception('Post was not created by story generation.');
            }
            $created_post_id = (int) $post->ID;

            // Assertions
            if (trim($post->post_title) !== $expected['title']) {
                throw new Exception('Post title does not match expected.');
            }

            if (empty($post->post_excerpt)) {
                throw new Exception('Post excerpt is empty.');
            }

            if (strpos($post->post_content, $expected['content']) === false) {
                throw new Exception('Post content does not include expected body text.');
            }

            if (!preg_match('/<img[^>]+src=\\"[^\\"]+\\"/i', $post->post_content)) {
                throw new Exception('Post content does not contain an <img> tag.');
            }

            $tag_names = wp_get_post_tags($created_post_id, array('fields' => 'names'));
            $missing_tags = array();
            foreach ($expected['tags'] as $tag) {
                if (!in_array($tag, $tag_names, true)) {
                    $missing_tags[] = $tag;
                }
            }
            if (!empty($missing_tags)) {
                throw new Exception('Missing expected tags: ' . implode(', ', $missing_tags));
            }

            $this->log_info('✅ Story generated and validated. Post ID: ' . $created_post_id);
            return 'Story generated with title, excerpt, image in content, and tags. Post ID: ' . $created_post_id;

        } finally {
            remove_filter('pre_http_request', $mock, 10);
            // Cleanup created post to keep environment tidy
            if (!empty($created_post_id)) {
                wp_delete_post($created_post_id, true);
            }
        }
    }
}