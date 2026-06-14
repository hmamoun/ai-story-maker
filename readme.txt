=== AI Story Maker ===
Contributors: hmamoun
Tags: ai, openai, content generation, blog automation, social media
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.3.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://www.storymakerplugin.com/
Author: Hayan Mamoun
Author URI: https://exedotcom.ca

Auto-generate, schedule & publish AI stories to WordPress + Facebook — no writing experience needed. Free to start.

== Description ==

**Stop staring at a blank editor.** AI Story Maker generates complete, publish-ready posts from a short prompt — with images, scheduling, and Facebook auto-posting built in.

Set it up once. Stories publish themselves.

Whether you run a blog, niche site, or client's WordPress, AI Story Maker handles the content while you focus on growing your audience.

---

**🆓 AI Story Enhancer — Free, No Credits Required**

Already have posts? Make them better instantly.

Select any text inside a post, describe what you want ("make this more engaging", "add a call to action", "rewrite for beginners"), and AI rewrites it on the spot. No subscription needed.

- Rewrite, expand, or improve any post content
- Generate SEO meta descriptions with one click
- Update tags and keywords automatically
- Access directly from Posts → All Posts

---

**⚡ AI Story Generation**

Generate complete WordPress posts from a prompt — images included.

- Powered by OpenAI (GPT models)
- Royalty-free images from Unsplash, Pexels, and Pixabay
- Schedule daily, weekly, or custom automated generation
- Reusable prompt templates with categories and tone controls
- Use image placeholders: `{img_unsplash:keyword}`
- Batch generate multiple stories at once
- Stories saved as drafts for review before publishing

---

**📣 Social Media Auto-Posting**

Publish to Facebook the moment a story goes live.

- Auto-posts title, excerpt, link, and hashtags
- Converts post tags to hashtags automatically
- Multi-account support
- LinkedIn, X (Twitter), and Instagram coming soon

---

**📊 Analytics & Performance**

Know what's working without leaving WordPress.

- Per-post views and click-through rates
- Content heatmaps and trend tracking
- Fully asynchronous — zero performance impact on your site

---

**🔧 Flexible Setup — Your Keys or Ours**

- Use a subscription (credits included) or your own OpenAI + Unsplash API keys
- Subscription credits take priority; API keys act as automatic fallback
- No lock-in — switch anytime from plugin settings

---

**🤖 WordPress 7.0 AI Agent Ready**

Three Abilities are registered automatically for orchestration by any WP 7.0 AI workflow:

- `ai-story-maker/generate-story` — generate a post from any configured prompt
- `ai-story-maker/enhance-content` — rewrite or improve existing post content
- `ai-story-maker/schedule-stories` — enable or disable weekly generation per user

Fully backwards compatible — silently skipped on WordPress 6.x.

---

**🖥️ Display Shortcodes**

Embed generated content anywhere on your site:

- `[aistma_posts_gadget]` — filterable, searchable story grid or list
- `[aistma_scroller]` — scrolling story ticker
- `[aistma_adsense]` — AdSense-ready display block
- `[aistma_data_overview]`, `[aistma_generation_calendar]`, `[aistma_recent_activity]` — dashboard widgets embeddable on any page

---

**Who is this for?**

Bloggers, niche site owners, content marketers, coaches, educators, and agencies managing multiple WordPress sites.

== Installation ==

1. Install via the WordPress Plugin Directory or upload the ZIP manually.
2. Activate through the **Plugins** screen.
3. Complete the welcome wizard — choose a subscription plan or connect your API keys.
4. Create a prompt template.
5. Generate your first story.

== External Services ==

This plugin makes requests to external services for core functionality:

**OpenAI API** (https://openai.com/)
- Transmits: Story prompts, request metadata
- Used for: AI-powered story and content generation
- Terms: https://openai.com/policies/terms-of-use

**Unsplash API** (https://unsplash.com/)
- Transmits: Image search keywords
- Used for: Fetching royalty-free images
- Terms: https://unsplash.com/terms

**Pexels API** (https://www.pexels.com/)
- Transmits: Image search keywords
- Used for: Fetching royalty-free images
- Terms: https://www.pexels.com/terms-of-service/

**Pixabay API** (https://pixabay.com/)
- Transmits: Image search keywords
- Used for: Fetching royalty-free images
- Terms: https://pixabay.com/terms/

**Exedotcom Gateway** (https://www.exedotcom.ca/)
- Transmits: Domain, admin email, plugin version
- Used for: Subscription verification and credits management
- Terms: https://www.exedotcom.ca/api-terms

== License & Privacy ==

Licensed under GPLv2 or later. No personal user data is stored beyond domain and email for subscription validation. Each external service maintains its own privacy policy (see External Services above).

== Screenshots ==

1. **Welcome wizard** — pick a prompt and generate your first AI story in under 60 seconds.
2. **Posts list** — one-click story generation and per-post AI Story Enhancer from your existing posts screen.
3. **AI Story Enhancer** — select any text, describe the improvement, and AI rewrites it instantly. No credits needed.
4. **SEO & Meta panel** — generate optimized meta descriptions and update tags with a single click.

== Frequently Asked Questions ==

= Is AI Story Maker free? =
Yes. The AI Story Enhancer is completely free with no usage limits. Story generation requires either a subscription plan or your own OpenAI API key.

= Do I need an OpenAI account? =
Not necessarily. You can use a subscription plan (credits are included) without setting up OpenAI yourself. If you prefer to use your own API key, you can enter it in the plugin settings.

= What is a prompt template? =
A reusable set of instructions that tells the AI what kind of story to write — topic, tone, length, target audience, categories, and image placeholders. You create it once and reuse it for every generation.

= Can I schedule stories to publish automatically? =
Yes — daily, weekly, or custom intervals. Stories are saved as drafts by default so you can review before they go live, or you can enable auto-publish.

= How does Facebook posting work? =
Connect your Facebook Page in the Social Media Integration settings. When a story is generated, the plugin automatically posts the title, excerpt, link, and hashtags to your page.

= What if generation fails? =
Check the plugin logs (AI Story Maker → Logs), verify your API key or subscription status, and confirm your server can reach external APIs. Most issues include a specific error message in the logs.

= Can I use my own OpenAI API key instead of a subscription? =
Yes. Enter your key under API Keys in the plugin settings. The subscription credits take priority when available; your key acts as the fallback.

= Does it work with WordPress 7.0 AI agents? =
Yes. Three Abilities are registered automatically when the plugin is active, making it orchestratable by any WP 7.0 AI workflow — no extra setup needed.

= Will using Abilities deduct my credits? =
Yes, the same as every other generation method. Ability-invoked generation goes through the same gateway as the manual button, wizard, and scheduler.

= Is my data safe? =
Story prompts are sent to OpenAI for generation. No personal user data is stored beyond your domain and admin email, which are used only for subscription validation. See the External Services section for full details.

= Can I generate content in languages other than English? =
Yes — specify the target language directly in your prompt template.

= How do I back up my prompt templates? =
Prompts are stored in the `wp_options` database table. A standard WordPress database backup covers them.

== Changelog ==

= 2.3.5 =
* Maintenance release: re-publishes 2.3.4 content under a clean tag to ensure the WP.org plugin directory serves the correct version.

= 2.3.4 =
* **NEW: Admin review notice** — after 5 story generations or 7 days of use, administrators see a friendly rating prompt. 4–5 stars go to WordPress.org; 1–3 stars reveal a feedback form.
* **NEW: WP 7.0 Abilities API** — registers `generate-story`, `enhance-content`, and `schedule-stories` for AI agent orchestration.
* **NEW: Enriched site-topic prompt** — the activation wizard uses your site title, tagline, and SEO meta for higher-quality first prompts.

= 2.3.3 =
* Maintenance release: republished 2.3.2 content under a new tag.

= 2.3.2 =
* **NEW: Frontend dashboard widgets as shortcodes** — Data Overview, Generation Calendar, and Recent Posts Activity now available via shortcode on any page.
* **FIX: Shortcode rendering on the frontend** — widget classes now load outside the admin context.

= 2.3.1 =
* **FIX: Insufficient credits after subscription cancellation** — wizard re-enrolls in the free tier when the gateway no longer authorizes generation.
* **FIX: Gateway as single source of truth for credits** — removed local credit ledger to prevent stale counts blocking generation.
* **FIX: API keys masked in settings** — only the last 10% of stored keys is shown.
* **FIX: Dashboard "Create Story Now"** — wizard initializes reliably from the dashboard widget.

= 2.3.0 =
* **NEW: Transaction history** — view credit history and transaction details in the admin dashboard.
* **NEW: Multiple photo sources** — Pexels and Pixabay added alongside Unsplash.
* **NEW: Keyword support in prompts** — add keywords for better SEO targeting.
* **SECURITY: Authenticated gateway client** — server-side authentication for gateway requests.
* **FIX: Subscription email updates** — users can update their subscription email anytime.

= 2.2.1 =
* Added privacy disclosure in wizard header.
* UI refinements and stability improvements.

= 2.2.0 =
* Added credit check with personal API fallback.
* Allow generation with user's own OpenAI key when no subscription credits remain.

= 2.1.3 =
* NEW: AI Story Enhancer for post-level content improvements.

= 2.0.3 =
* Major analytics improvements and widget upgrades.

== Love the Plugin? ==
Leave a review on WordPress.org — it helps more people find AI Story Maker and keeps development going. ⭐⭐⭐⭐⭐
