# AI Story Maker — WordPress Plugin

**Auto-generate, schedule & publish AI stories to WordPress + Facebook — no writing experience needed. Free to start.**

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)](https://wordpress.org/plugins/ai-story-maker/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tested up to](https://img.shields.io/badge/Tested%20up%20to-WP%207.0-blue)](https://wordpress.org/plugins/ai-story-maker/)

[🌐 Website](https://www.storymakerplugin.com/) · [📦 WordPress.org](https://wordpress.org/plugins/ai-story-maker/) · [📖 Docs](docs/abilities-api.md) · [☕ Support the project](https://buymeacoffee.com/78vcTEm4i)

---

## What it does

Stop staring at a blank editor. AI Story Maker generates complete, publish-ready WordPress posts from a short prompt — with images, scheduling, and Facebook auto-posting built in.

Set it up once. Stories publish themselves.

---

## Features

### 🆓 AI Story Enhancer — Free, No Credits Required
Improve existing posts without a subscription.

- Select any text inside a post and describe the improvement
- Rewrite, expand, or improve any post content
- Generate SEO meta descriptions with one click
- Update tags and keywords automatically
- Access directly from **Posts → All Posts**

### ⚡ AI Story Generation
Generate complete WordPress posts from a prompt — images included.

- Powered by OpenAI (GPT models)
- Royalty-free images from Unsplash, Pexels, and Pixabay
- Schedule daily, weekly, or custom automated generation
- Reusable prompt templates with categories and tone controls
- Image placeholders: `{img_unsplash:keyword}`
- Batch generate multiple stories at once
- Stories saved as drafts for review before publishing

### 📣 Social Media Auto-Posting
- Auto-posts to Facebook when a story goes live
- Converts post tags to hashtags automatically
- Multi-account support
- LinkedIn, X (Twitter), and Instagram coming soon

### 📊 Analytics & Performance
- Per-post views and click-through rates
- Content heatmaps and trend tracking
- Fully asynchronous — zero performance impact

### 🔧 Flexible Credentials
- Use a **subscription** (credits included) or your own **OpenAI + Unsplash API keys**
- Subscription credits take priority; API keys act as automatic fallback
- Switch anytime from plugin settings

---

## WordPress 7.0 AI Agent Integration

AI Story Maker is AI agent-ready. Three Abilities are registered automatically when the plugin is active:

| Ability | Description |
|---|---|
| `ai-story-maker/generate-story` | Generate a new story using configured prompts |
| `ai-story-maker/enhance-content` | Rewrite or improve existing post content |
| `ai-story-maker/schedule-stories` | Enable or disable weekly auto-generation per user |

**Example — invoke from any WP 7.0 workflow:**

```php
$result = wp_invoke_ability( 'ai-story-maker/generate-story', [
    'prompt_id' => 'my-prompt-id',
] );

if ( $result['success'] ) {
    // $result['post_id'] and $result['post_url'] are ready
}
```

Credits are deducted the same way as every other generation method. On WordPress 6.x the plugin functions normally — Abilities registration is silently skipped.

→ Full documentation: [docs/abilities-api.md](docs/abilities-api.md)

---

## Display Shortcodes

| Shortcode | Description |
|---|---|
| `[aistma_posts_gadget]` | Filterable, searchable story grid or list |
| `[aistma_scroller]` | Scrolling story ticker |
| `[aistma_adsense]` | AdSense-ready display block |
| `[aistma_data_overview]` | Data overview dashboard widget |
| `[aistma_generation_calendar]` | Generation calendar widget |
| `[aistma_recent_activity]` | Recent posts activity widget |

**Example with options:**
```
[aistma_posts_gadget posts_per_page="8" layout="grid" show_search="true"]
```

---

## Installation

1. Install via the [WordPress Plugin Directory](https://wordpress.org/plugins/ai-story-maker/) or upload the ZIP manually via **Plugins → Add New → Upload Plugin**.
2. Activate through the **Plugins** screen.
3. Complete the welcome wizard — choose a subscription plan or connect your API keys.
4. Create a prompt template.
5. Generate your first story.

---

## Writing Effective Prompts

- Be specific about the topic and target audience
- Specify desired length and tone
- Use image placeholders: `{img_unsplash:clean energy,solar}`
- The first matched image becomes the featured image; others are placed inline

**General Instructions** (set once, applied to all prompts) control global rules like article length and tone. **Prompt List** items generate individual content pieces on their own schedule.

---

## External Services

This plugin connects to third-party APIs:

| Service | Purpose | Data Sent |
|---|---|---|
| [OpenAI](https://openai.com/) | Story and content generation | Prompt text, request metadata |
| [Unsplash](https://unsplash.com/) | Royalty-free images | Image search keywords |
| [Pexels](https://www.pexels.com/) | Royalty-free images | Image search keywords |
| [Pixabay](https://pixabay.com/) | Royalty-free images | Image search keywords |
| [Exedotcom Gateway](https://exedotcom.ca/) | Subscription & credits management | Domain, admin email, plugin version |

No personal user data is collected or stored beyond domain and email for subscription validation.

- [OpenAI Terms](https://openai.com/policies/terms-of-use) · [OpenAI Privacy](https://openai.com/policies/privacy-policy)
- [Unsplash Terms](https://unsplash.com/terms)
- [Exedotcom API Terms](https://exedotcom.ca/api-terms)

---

## Changelog

### 2.3.5
Maintenance release: re-publishes 2.3.4 content under a clean tag.

### 2.3.4
- **NEW:** Admin review notice after 5 generations or 7 days of use
- **NEW:** WP 7.0 Abilities API — `generate-story`, `enhance-content`, `schedule-stories`
- **NEW:** Enriched site-topic prompt using site title, tagline, and SEO meta

### 2.3.2
- **NEW:** Frontend dashboard widgets as shortcodes
- **FIX:** Shortcode rendering outside the admin context

### 2.3.1
- **FIX:** Insufficient credits after subscription cancellation
- **FIX:** Gateway as single source of truth for credits
- **FIX:** API keys masked in settings page

### 2.3.0
- **NEW:** Transaction history in admin dashboard
- **NEW:** Pexels and Pixabay as additional image sources
- **NEW:** Keyword support in prompts for SEO targeting
- **SECURITY:** Server-side authentication for gateway requests

### 2.1.3
- **NEW:** AI Story Enhancer for post-level content improvements

---

## Contributing

Issues and pull requests welcome via [GitHub](https://github.com/hmamoun/ai-story-maker).

---

## License

[GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html) — free for personal and commercial use.
