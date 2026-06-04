# AI Story Maker — WP 7.0 Abilities API Guide

AI Story Maker exposes three **Abilities** through the WordPress 7.0 Abilities API. This lets any WP 7.0 AI agent or workflow plugin discover and trigger the plugin's core features — story generation, content enhancement, and scheduling — without writing custom integration code.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [How Abilities Work](#how-abilities-work)
3. [Ability: generate-story](#ability-generate-story)
4. [Ability: enhance-content](#ability-enhance-content)
5. [Ability: schedule-stories](#ability-schedule-stories)
6. [Calling Abilities from PHP](#calling-abilities-from-php)
7. [Practical Recipes](#practical-recipes)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

| Requirement | Detail |
|---|---|
| WordPress | 7.0 or later |
| AI Story Maker | 2.4.0 or later |
| Active plan | Free plan or paid — any plan with available credits |
| User capability | `edit_posts` minimum for all three abilities |

On WordPress 6.x the abilities are silently inactive. The plugin continues to work normally; only the Abilities API registration is skipped.

---

## How Abilities Work

WordPress 7.0 maintains a central **Ability Registry**. When AI Story Maker loads, it registers three named abilities. Any plugin or AI agent running on the same site can then:

1. **Discover** the ability by querying the registry
2. **Read** its input/output schema to understand what parameters it needs and what it returns
3. **Invoke** it — WordPress validates the input, checks permissions, and runs the callback

You never call AI Story Maker's internal classes directly. You invoke the ability by name, pass a parameters array, and get a structured response back.

---

## Ability: `generate-story`

**Full name:** `ai-story-maker/generate-story`

Generates a new AI-powered story and publishes it as a WordPress post (draft or published, depending on your prompt settings).

### Input parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `prompt_id` | string | No | — | ID of a specific prompt to run. If omitted, all active prompts run. |
| `force` | boolean | No | `false` | Override the concurrent-generation lock. Use only if a previous run got stuck. |

### Output

| Field | Type | Description |
|---|---|---|
| `success` | boolean | `true` if at least one story was generated. |
| `post_id` | integer \| null | ID of the created post. `null` when running all prompts (batch mode). |
| `post_url` | string \| null | Permalink of the created post. `null` in batch mode. |
| `message` | string | Human-readable result or error message. |

### Examples

**Generate using all active prompts (batch mode):**

```php
$result = wp_invoke_ability( 'ai-story-maker/generate-story', [] );

if ( $result['success'] ) {
    echo 'Stories generated.';
} else {
    echo 'Failed: ' . $result['message'];
}
```

**Generate using one specific prompt:**

```php
$result = wp_invoke_ability( 'ai-story-maker/generate-story', [
    'prompt_id' => 'tech-news-daily',
] );

if ( $result['success'] ) {
    $post_url = $result['post_url'];
    // e.g. share $post_url to social media
}
```

**Force-run after a stuck lock:**

```php
$result = wp_invoke_ability( 'ai-story-maker/generate-story', [
    'force' => true,
] );
```

### What happens internally

- Checks that the active plan has credits available. Returns an error (not an exception) if credits are exhausted.
- Goes through the gateway — no direct OpenAI calls, no BYOAPI path.
- Respects your prompt's `auto_publish` setting. If the prompt is set to draft, the post is created as a draft.

### Common errors

| Message | Cause | Fix |
|---|---|---|
| `No active plan or credits found.` | Plan expired or credits at zero. | Visit storymakerplugin.com/#pricing. |
| `Story generation is already in progress.` | Concurrent lock is active. | Wait a few minutes or pass `force: true`. |
| `prompt_id is valid and your plan has available credits.` | Prompt ID not found. | Check the prompt ID in Settings → Prompts. |

---

## Ability: `enhance-content`

**Full name:** `ai-story-maker/enhance-content`

Improves or rewrites a block of existing post content using AI. Returns the enhanced HTML, ready to replace the original.

### Input parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `post_id` | integer | **Yes** | — | ID of the post being edited. |
| `selected_text` | string | **Yes** | — | The text passage to enhance. |
| `instruction` | string | **Yes** | — | What to do — e.g. `"make it more concise"`, `"rewrite in a friendlier tone"`. |
| `operation` | string | No | `text_improve` | Operation type. One of: `text_improve`, `image_insert`, `image_replace`. |

### Output

| Field | Type | Description |
|---|---|---|
| `success` | boolean | `true` if enhancement succeeded. |
| `improved_content` | string \| null | Enhanced HTML, ready to save. `null` on failure. |
| `message` | string | Human-readable result or error message. |

### Examples

**Improve a passage:**

```php
$result = wp_invoke_ability( 'ai-story-maker/enhance-content', [
    'post_id'       => 42,
    'selected_text' => 'The event was held last Saturday and many people came.',
    'instruction'   => 'Rewrite this to sound more engaging and specific.',
] );

if ( $result['success'] ) {
    // $result['improved_content'] contains the new HTML — save it however you need
    update_post_meta( 42, '_my_enhanced_intro', $result['improved_content'] );
}
```

**Insert an image into a section:**

```php
$result = wp_invoke_ability( 'ai-story-maker/enhance-content', [
    'post_id'       => 42,
    'selected_text' => '<h2>Downtown Farmers Market</h2>',
    'instruction'   => 'Add a relevant market photo after this heading.',
    'operation'     => 'image_insert',
] );
```

### Permissions note

The calling user must have `edit_post` capability for the specific `post_id`, not just a general `edit_posts` role. The ability returns a permission error (not an exception) if the user can't edit that post.

### Common errors

| Message | Cause | Fix |
|---|---|---|
| `post_id, selected_text, and instruction are required.` | Missing a required field. | Provide all three required parameters. |
| `You do not have permission to edit this post.` | User can't edit that post. | Run as a user with edit access to that post. |
| `Gateway returned HTTP 4xx.` | Gateway rejected the request. | Check your active plan and gateway API key. |

---

## Ability: `schedule-stories`

**Full name:** `ai-story-maker/schedule-stories`

Enables or disables weekly automatic story generation for a user, and sets which prompt to use.

### Input parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `enabled` | boolean | **Yes** | — | `true` to enable weekly auto-generation, `false` to disable. |
| `prompt_id` | string \| integer | No | Current saved value | Prompt to use for scheduled generation. |
| `user_id` | integer | No | Current user | User to configure. Requires `manage_options` to set another user's schedule. |

### Output

| Field | Type | Description |
|---|---|---|
| `success` | boolean | `true` if the schedule was updated. |
| `enabled` | boolean | The new enabled state. |
| `prompt_id` | string \| integer \| null | The prompt ID now saved for this user. |
| `next_generation` | string \| null | Estimated next run date/time (WordPress timezone, `Y-m-d H:i:s`). `null` if disabled. |
| `message` | string | Human-readable confirmation. |

### Examples

**Enable weekly generation for the current user:**

```php
$result = wp_invoke_ability( 'ai-story-maker/schedule-stories', [
    'enabled'   => true,
    'prompt_id' => 'weekly-roundup',
] );

if ( $result['success'] ) {
    echo 'Next story scheduled for: ' . $result['next_generation'];
}
```

**Disable auto-generation:**

```php
wp_invoke_ability( 'ai-story-maker/schedule-stories', [
    'enabled' => false,
] );
```

**Configure another user's schedule (admin only):**

```php
// Requires manage_options
$result = wp_invoke_ability( 'ai-story-maker/schedule-stories', [
    'enabled'   => true,
    'prompt_id' => 'local-news',
    'user_id'   => 7,
] );
```

### How scheduling works

The ability only sets the preference. Actual generation happens when WP-Cron fires the `aistma_generate_story_event` hook (daily). At that point, the scheduler checks all users with `enabled = true`, confirms it has been at least 7 days since their last story, and triggers generation. The `next_generation` value in the response is an estimate based on that logic.

---

## Calling Abilities from PHP

WP 7.0 provides `wp_invoke_ability()` for direct PHP invocation:

```php
$result = wp_invoke_ability( 'ability-name', $params );
```

The function returns the ability's output array on success, or a `WP_Error` if the ability isn't registered, the permission check fails, or the input fails schema validation.

**Always check for `WP_Error` first:**

```php
$result = wp_invoke_ability( 'ai-story-maker/generate-story', [] );

if ( is_wp_error( $result ) ) {
    // Ability not found, permission denied, or invalid input
    error_log( $result->get_error_message() );
    return;
}

if ( $result['success'] ) {
    // Handle success
}
```

**Check if an ability is available before calling it:**

```php
if ( wp_ability_exists( 'ai-story-maker/generate-story' ) ) {
    $result = wp_invoke_ability( 'ai-story-maker/generate-story', [] );
}
```

---

## Practical Recipes

### Recipe 1 — Weekly content pipeline with social share

Trigger weekly story generation and pass the result to a social-share ability from another plugin:

```php
add_action( 'my_weekly_content_pipeline', function() {
    // Generate the story
    $story = wp_invoke_ability( 'ai-story-maker/generate-story', [
        'prompt_id' => 'weekly-roundup',
    ] );

    if ( is_wp_error( $story ) || ! $story['success'] || ! $story['post_url'] ) {
        return;
    }

    // Pass the URL to a social-share ability (hypothetical third-party plugin)
    if ( wp_ability_exists( 'social-publisher/share-url' ) ) {
        wp_invoke_ability( 'social-publisher/share-url', [
            'url'     => $story['post_url'],
            'message' => get_the_title( $story['post_id'] ),
        ] );
    }
} );
```

### Recipe 2 — Auto-enhance a post on save

Run the enhancement ability whenever a specific post type is saved:

```php
add_action( 'save_post_news_brief', function( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! wp_ability_exists( 'ai-story-maker/enhance-content' ) ) return;

    $intro = get_post_field( 'post_excerpt', $post_id );
    if ( empty( $intro ) ) return;

    $result = wp_invoke_ability( 'ai-story-maker/enhance-content', [
        'post_id'       => $post_id,
        'selected_text' => $intro,
        'instruction'   => 'Make this excerpt more compelling and under 30 words.',
    ] );

    if ( ! is_wp_error( $result ) && $result['success'] ) {
        wp_update_post( [
            'ID'           => $post_id,
            'post_excerpt' => wp_strip_all_tags( $result['improved_content'] ),
        ] );
    }
} );
```

### Recipe 3 — Bulk-enable weekly scheduling for a user group

```php
// Run once via WP-CLI or a one-off admin action
$editors = get_users( [ 'role' => 'editor' ] );

foreach ( $editors as $user ) {
    wp_invoke_ability( 'ai-story-maker/schedule-stories', [
        'enabled'   => true,
        'prompt_id' => 'editor-picks',
        'user_id'   => $user->ID,
    ] );
}
```

---

## Troubleshooting

**`wp_invoke_ability` is undefined**
WordPress version is below 7.0. Abilities are not available on earlier versions.

**`WP_Error` with code `ability_not_found`**
AI Story Maker 2.4.0+ is not active, or the plugin did not finish loading. Check that the plugin is active and no PHP errors appear in `debug.log`.

**`WP_Error` with code `rest_forbidden` or similar permission error**
The user invoking the ability does not have `edit_posts`. Make sure the call runs in a context where a capable user is logged in, or use `wp_set_current_user()` to set the correct user before invoking.

**`No active plan or credits found`**
The domain has no active subscription or has used all credits. The ability returns this as a structured error (not a `WP_Error`) in the `message` field. Direct the user to `storymakerplugin.com/#pricing`.

**`Story generation is already in progress`**
A previous generation call is still holding the lock (10-minute TTL). Wait and retry, or pass `force: true` to override if you're confident the previous run is stalled.
