=== AI Story Maker ===
Contributors: hmamoun
Tags: ai, content creation, blog automation, article generation, wordpress ai plugin
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://www.storymakerplugin.com/
Author: Hayan Mamoun
Author URI: https://exedotcom.ca

AI-powered WordPress plugin that generates high-quality stories instantly with OpenAI and Unsplash. Includes AI Story Enhancer for content upgrades.

== Description ==

**AI Story Maker** transforms your WordPress into an intelligent storytelling engine. Seamlessly generate captivating stories and articles enhanced with dynamic visuals—within seconds. No writing experience needed.

**NEW: AI Story Enhancer**
Upgrade any post with professional-quality enhancements by simply selecting text and choosing how you want it improved. Save time, improve quality, and boost reader engagement.

Ideal for bloggers, marketers, coaches, educators, and content creators.

== Key Features ==

**AI Story Enhancer**
- Smart text selection and contextual enhancements
- Instant AI rewrite, expand, and improve tools
- One-click access from your post list
- Free to use, no credits required

**AI-Powered Story Generation**
- Leverages OpenAI to generate unique content
- Royalty-free image integration via Unsplash
- Schedule automatic content generation
- Create & manage custom prompts with categories

**Built-In Analytics**
- Traffic logs and heatmaps
- Post performance tracking and click stats

**Advanced Content Display**
- Filterable and searchable story widgets ([aistma_posts])
- Story scroller with shortcode ([aistma_scroller])
- Seamless integration with any theme

**Social Media Integration**
- Auto-publish to Facebook (LinkedIn, X, and Instagram coming soon)
- Convert tags to hashtags
- Smart scheduling and multi-account support

**Developer Friendly**
- Use subscription credits or your own OpenAI/Unsplash keys
- Automatic fallback: generate stories using master API when credits are available, no subscription required
- Clean shortcode and widget setup
- Multilingual ready

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress plugin screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Complete the welcome wizard to choose a subscription plan or connect your own API keys.
4. Set up prompt rules and preferences.
5. Start generating and enhancing content.

== After Installation ==
1. Choose a subscription or connect your API keys
2. Set up prompt rules and preferences
3. Start generating and enhancing content

== Support & Documentation ==
- Full setup and user guide at storymakerplugin.com
- Developer docs and API terms available

== External Services ==

This plugin makes requests to external services for core functionality:

**OpenAI API** (https://openai.com/)
- Transmits: Story prompts, request metadata
- Used for: AI-powered story generation
- Terms: https://openai.com/policies/terms-of-use

**Unsplash API** (https://unsplash.com/)
- Transmits: Image search queries
- Used for: Fetching royalty-free images for stories
- Terms: https://unsplash.com/terms

**Exedotcom Gateway** (https://www.exedotcom.ca/)
- Transmits: Domain, admin email, plugin version
- Used for: Subscription verification, credits management, license validation
- Endpoints: `/verify-subscription`, `/ensure-startup-credits`
- Data sent to ensure-startup-credits: Domain, admin email (for initial credit setup)
- Terms: https://www.exedotcom.ca/api-terms

== License & Privacy ==
- Licensed under GPLv2 or later
- No personal user data is collected or stored beyond domain/email for subscription validation
- Each external service has its own privacy policy (see External Services section)

== Screenshots ==

1. Welcome wizard — choose a prompt and generate your first AI story in seconds.
2. Posts list integration — one-click "Generate AI Stories" button and per-post "AI Story Enhancer" action.
3. AI Story Enhancer — select any text in your post and choose how to rewrite, expand, or improve it.
4. SEO & Meta panel — generate optimised meta descriptions for your posts with a single click.

== Changelog ==

= 2.3.2 =
* **FIX: Insufficient credits after subscription cancellation** -- wizard now re-enrolls in the free tier when the gateway no longer authorizes generation, not only when no API key is stored
* **FIX: Gateway is the single source of truth for credits** -- removed the local credit ledger so a stale local count cannot block generation when the gateway has valid credits
* **FIX: Wizard auto-enrollment** -- calls /wizard-enroll-free and stores the returned gateway API key, then re-checks the subscription before generating
* **FIX: API keys masked in settings page** -- only last 10% of stored keys is shown
* **FIX: Dashboard 'Create Story Now'** -- wizard initializes reliably from the dashboard widget; rating modal excluded from dashboard to prevent fadeIn crash

= 2.3.0 =
* **NEW: Display User Transactions** — View credit history and transaction details in admin dashboard
* **NEW: Multiple Photo Resources** — Support for Pexels and Pixabay in addition to Unsplash
* **NEW: Keyword Support in Prompts** — Add keywords to prompts for better SEO optimization
* **NEW: Externalized Styles** — Migrated inline CSS/JS to external files for better performance
* **SECURITY: Authenticated Gateway Client Flow** — Server-side authentication for gateway requests, removed browser-exposed auth
* **FIX: Subscription Email Updates** — Users can now update their subscription email anytime for account management
* **IMPROVED: Subscription Security** — CSRF protection with proper nonce verification
* **IMPROVED: Type Consistency** — Fixed null/int type handling for managed subscriptions
* **TESTED: Full Feature Coverage** — Added unit tests for auth headers and subscription detection

= 2.2.1 =
* Added privacy disclosure note in wizard header
* Improved user transparency about startup credits
* UI refinements and stability improvements

= 2.2.0 =
* Fixed wizard modal centering for prompts tab
* Added credit check with personal API fallback
* Allow story generation with user's own OpenAI API key when no credits available
* Improved error messages and user redirects to plans tab

= 2.1.4 =
* Bug fixes and performance improvements
* Enhanced stability and code quality

= 2.1.3 =
* NEW: AI Story Enhancer feature for post-level improvements
* Enhanced UX and seamless WP integration

= 2.0.3 =
* Major analytics improvements
* Widget and display upgrades

== Love the Plugin? ==
Support development by leaving a review or buying a coffee ☕

== Stay Updated ==
Subscribe for feature updates and tutorials at storymakerplugin.com
