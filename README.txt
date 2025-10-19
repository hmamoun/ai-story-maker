=== AI Story Maker ===
Contributors: hmamoun
Tags: ai, content creation, blog automation, article generation, wordpress ai plugin
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://www.storymakerplugin.com/
Author: Hayan Mamoun
Author URI: https://exedotcom.ca

AI-powered WordPress plugin to automatically generate unique stories, articles, and visuals using OpenAI and Unsplash APIs. Now featuring AI Story Enhancer for intelligent content improvement.

== Description ==

**AI Story Maker** helps you instantly generate unique, high-quality WordPress posts using AI and royalty-free images. It integrates with OpenAI for text generation and Unsplash for fetching dynamic images, saving you hours of content creation time.

**🆕 NEW: AI Story Enhancer** - Transform your content creation workflow with intelligent, AI-powered text improvement capabilities directly within your WordPress admin interface. Simply select any text in your posts and enhance it with AI for professional-quality results.

Whether you're a blogger, marketer, or educator, AI Story Maker helps you build a full content strategy effortlessly.

== Features ==

### AI Story Enhancer (NEW!)
- **Smart Text Selection** – Simply select any text in your post content and enhance it with AI
- **Context-Aware Improvements** – AI understands your content context and provides relevant enhancements
- **One-Click Access** – "AI Story Enhancer" link appears under every post in your posts list
- **Free Usage** – Enhanced content is free to use with usage tracking
- **Professional Results** – AI-powered enhancements that rival professional editors
- **Seamless Integration** – Works directly within your existing WordPress workflow

### Core Features
- **Subscription-Based Access** – Access AI generation through package subscriptions including free options.
- **AI-Generated Stories** – Create unique, professional stories and articles using OpenAI.
- **Smart Image Integration** – Automatic dynamic, royalty-free images from Unsplash.
- **Social Media Integration** – Automatically publish stories to social media platforms (starting with Facebook, with Twitter/X, LinkedIn, and Instagram coming soon).
- **Posts Display Widget** – Searchable and filterable posts display with analytics.
- **Prompt Editor** – Build, customize, and organize your own prompts.
- **Custom Story Scroller** – Display stories dynamically on the frontend.
- **Auto Model Attribution** – Add a model credit note automatically for transparency.
- **Analytics Dashboard** – Comprehensive analytics with heatmaps and insights.
- **Traffic Logging** – Monitor post views and engagement.
- **Widget System** – Dashboard widgets for data cards, activity, and calendar views.
- **Logging System** – Monitor and debug AI generations easily.

== After Installing ==

The plugin operates through subscription packages that provide AI generation credits and features:

1. **Subscribe to Packages** – Choose from various subscription packages including free options that provide credits for AI story generation.

2. **Alternative: Use Own API Keys** – Advanced users can configure their own OpenAI and Unsplash API keys in the settings if preferred.

3. **Configure Your Preferences** – Set up story generation settings, select authors, and customize your content strategy.

The plugin saves your domain and email to maintain subscription integrity and communicate important updates to your subscription email.

== Story Generation Settings ==

- **Log Retention (Days)**: Define how long logs are kept. Set `0` to retain them indefinitely.
- **Generate New Stories Every (Days)**: Schedule AI story creation automatically every few days.
- **Select Story Author**: Choose a WordPress user to assign as the author of AI-generated content.

== Managing Prompts ==

The **Prompt Editor** allows you to build and control the instructions the AI follows.

### How to Add a New Prompt

1. Go to **AI Story Maker > Prompt Editor**.
2. Click **Add New Prompt**.
3. A new empty row appears.
4. Fill in:
   - **Prompt Text** (the instruction for the AI)
   - **Category** (WordPress category to publish in)
   - Other options (e.g., Auto-publish, Author, Photo placeholders)

> **Remember**: After adding, editing, or deleting prompts, you must click **Save Changes** to confirm.

### How to Delete a Prompt

- Click the **Delete** button beside the prompt.
- The row will be marked for deletion.
- Press **Save Changes** to complete.

### General Instructions vs Prompt Text

- **General Instructions**: Global rules applied to **all** prompts (e.g., article length, tone).
- **Prompt List**: Specific prompts that generate different content topics individually.

General Instructions are combined automatically with each prompt during generation.

== Displaying Generated Stories ==

### Primary Display: Posts Widget with Search and Filter

The main way to display your generated stories is through the posts widget shortcode that provides search and filter capabilities:

**[aistma_posts]** - Displays a searchable and filterable grid of your AI-generated posts

You can add this shortcode:
- In any WordPress page or post
- Inside a shortcode block
- In a widget area that supports shortcodes
- In a PHP template file using: echo do_shortcode('[aistma_posts]');

The widget automatically includes:
- Search functionality
- Category filtering
- Post thumbnails with images
- Responsive grid layout
- Ajax pagination

### Additional Display Options

AI Story Maker saves AI-generated content as regular WordPress posts. 
This means your stories will appear automatically wherever your theme displays posts, such as:

- Blog pages
- Post archives
- Menus and featured content areas

=== Shortcode: [aistma_scroller] ===

The shortcode [aistma_scroller] displays a dynamic scrolling bar of the latest AI-generated stories.

You can add the shortcode:
- In any WordPress page or post
- Inside a shortcode block
- In a widget area that supports shortcodes
- In a PHP template file using: echo do_shortcode('[aistma_scroller]');

=== How to Use AI Story Enhancer ===

1. Navigate to **Posts → All Posts** in your WordPress admin
2. Find any post and click **"AI Story Enhancer"** under the post title
3. In the editor, select any text in the content preview area
4. Enter your enhancement instructions (e.g., "Make this more engaging", "Add more details", "Improve readability")
5. Click **"Improve"** to see AI-powered enhancements
6. Review the improved text and click **"Save Changes"** when satisfied

**Pro Tips:**
- Use specific instructions for better results (e.g., "Make this more conversational" vs. "Improve this")
- The AI understands context, so it will enhance text appropriately for your content type
- You can enhance multiple sections of the same post
- Changes are tracked automatically - the save button only enables when you have unsaved changes

=== How to Use Core Features ===

1. Edit a page or post in WordPress.
2. Add a new Shortcode Block (or paste directly).
3. Enter: [aistma_posts] for the main display or [aistma_scroller] for the scrolling bar
4. Save the page.

The displays adapt to your site's theme styles automatically. Additional CSS customization is possible if needed.

=== Important Notes ===

- Both shortcodes are fully responsive for mobile devices.
- Normal WordPress post listings are not affected.

== Social Media Integration ==

AI Story Maker includes comprehensive social media integration to automatically publish your AI-generated stories across multiple platforms.

=== Supported Platforms ===

- **Facebook Pages** – Fully supported with automatic link sharing
- **Twitter/X** – Coming soon with hashtag optimization
- **LinkedIn Company Pages** – Coming soon for professional content
- **Instagram Business Accounts** – Coming soon for visual content

=== Key Features ===

- **Auto-Publish New Stories** – Automatically share new AI-generated content to connected accounts
- **Manual Publishing** – Publish individual posts or use bulk actions for multiple posts
- **Smart Hashtag Integration** – Convert WordPress post tags to social media hashtags
- **Custom Hashtags** – Add default hashtags to all social media posts
- **Multiple Account Support** – Connect multiple accounts per platform
- **Connection Testing** – Verify account credentials and connection status

=== Setup Instructions ===

1. Navigate to **AI Story Maker > Social Media Integration**
2. Configure global settings (auto-publish, hashtags, etc.)
3. Add social media accounts with required credentials
4. Test connections to verify setup
5. Enable accounts for automatic or manual publishing

=== Facebook Setup ===

To connect a Facebook page:
1. Create a Facebook App in Facebook Developer Console
2. Generate a Page Access Token with required permissions
3. Get your Page ID from your Facebook page settings
4. Enter credentials in the plugin and test the connection

For detailed setup instructions, visit the plugin documentation.

== Screenshots ==

_(Coming soon)_

== Plugin File Structure ==

ai-story-maker/
├── ai-story-maker.php
├── LICENSE
├── README.txt
├── uninstall.php
├── admin/
│   ├── templates/
│   │   ├── analytics-template.php
│   │   ├── general-settings-template.php
│   │   ├── log-table-template.php
│   │   ├── prompt-editor-template.php
│   │   ├── subscriptions-template.php
│   │   └── welcome-tab-template.php
│   └── widgets/
│       ├── data-cards-widget.php
│       ├── posts-activity-widget.php
│       ├── story-calendar-widget.php
│       └── widgets-manager.php
├── includes/
│   ├── class-aistma-log-manager.php
│   ├── class-aistma-posts-gadget.php
│   ├── class-aistma-story-generator.php
│   ├── class-aistma-traffic-logger.php
│   └── shortcode-story-scroller.php
├── languages/
│   ├── ai-story-maker-es_ES.mo
│   ├── ai-story-maker-es_ES.po
│   ├── ai-story-maker-fr_CA.mo
│   ├── ai-story-maker-fr_CA.po
│   └── ai-story-maker.pot
└── public/
    ├── css/
    │   ├── aistma-style.css
    │   └── public.css
    ├── images/
    │   └── logo.svg
    ├── js/
    │   └── public.js
    └── templates/
        └── aistma-post-template.php

== Guide to Writing Prompts ==

- Example prompt:  
  `Write a news article about the latest trends in clean energy.`

The plugin automatically handles image integration and ensures:
- Relevant images are placed correctly within the content.
- An attribution note about the AI model is automatically added.

== Frequently Asked Questions ==

= How do subscription packages work? =
The plugin offers various subscription packages including free options. Packages provide credits for AI story generation and access to premium features.

= Can I use my own API keys instead? =
Yes, advanced users can configure their own OpenAI and Unsplash API keys in the settings as an alternative to subscription packages.

= Can I customize article formats? =
Yes, edit the "General Instructions" field to control structure, tone, and style.

= Can I disable automatic generation? =
Yes, set "Generate New Stories Every" to `0` to disable scheduled stories.

= What analytics are available? =
The plugin provides comprehensive analytics including story generation heatmaps, post activity tracking, tag click analytics, and traffic insights.

== Changelog ==

= 2.0.3 =
- Enhanced analytics dashboard with improved performance and reliability
- Fixed widget promotion content removal for cleaner analytics interface
- Improved subscription system integration and error handling
- Updated dashboard widgets with consistent styling and functionality
- Enhanced security measures and input validation
- Bug fixes and performance optimizations

= 2.0.1 =
- Added comprehensive analytics dashboard with heatmaps and insights
- Introduced subscription package system with free options
- Added posts display widget with search and filtering capabilities
- Implemented traffic logging and post view analytics
- Added dashboard widgets for data cards, activity tracking, and calendar views
- Enhanced security with proper input validation and escaping
- Improved user interface with better navigation and settings organization
- Added support for WordPress timezone handling
- Enhanced image integration with automatic processing
- Improved error handling and logging system

= 1.0 =
- Initial release with AI story generation, image integration, logging, and prompt editor.

== Upgrade Notice ==

= 2.0.3 =
- Recommended update with enhanced analytics, improved performance, and bug fixes. All existing functionality remains compatible.

= 2.0.1 =
- Major update with analytics dashboard, subscription system, and enhanced features. Existing users can continue using their API keys or switch to subscription packages.

= 1.0 =
- First release. Make sure to configure API keys after activation.

== External Services Disclosure ==

This plugin connects to third-party APIs:

1. **OpenAI API**
   - Purpose: Generate AI content.
   - Data sent: Prompt text and system instructions.
   - [OpenAI Terms](https://openai.com/policies/terms-of-use) | [OpenAI Privacy Policy](https://openai.com/policies/privacy-policy)

2. **Unsplash API**
   - Purpose: Fetch royalty-free images.
   - Data sent: Keywords only (no personal data).
   - [Unsplash Terms](https://unsplash.com/terms) | [Unsplash Privacy Policy](https://unsplash.com/privacy)

3. **Exedotcom API Gateway**
   - Purpose: Subscription management and package access.
   - Data sent: Domain and subscription email for integrity verification.
   - Privacy: Only domain and email are transmitted for subscription management.

4. **Social Media Platforms**
   - Purpose: Publish AI-generated stories to connected social media accounts.
   - Data sent: Post titles, excerpts, permalinks, and hashtags.
   - Platforms: Facebook, Twitter/X, LinkedIn, Instagram (when configured).
   - Privacy: Only post content and metadata are shared; no personal user data is transmitted.

== How AI Story Maker Retrieves General Instructions ==

AI Story Maker dynamically fetches the general AI system instructions from the Exedotcom API Gateway instead of hardcoding them inside the plugin.
	-	The plugin makes a secure HTTP request to retrieve the latest best-practice instructions.
	-	If the request fails or returns no data, a default internal fallback is used to ensure smooth operation.
	-	The retrieved instructions are cached temporarily to minimize remote calls and enhance performance.
	-	Allows future improvements and best practices without needing plugin updates.
	-	Ensures the generated articles continue to follow the latest content structure and SEO guidelines.

Privacy note:
	-	The plugin saves your domain and email to maintain subscription integrity and communicate updates.
	-	Only the site URL and server IP address are transmitted for simple tracking and security purposes.
	-	See our API terms of service at https://exedotcom.ca/api-terms (optional link if you plan to add later).
   -  visit this address to see the latest provided instructions: https://exedotcom.ca/wp-json/exaig/v1/aistma-general-instructions

No additional personal user data is collected or stored.

== FAQ ==

= What is AI Story Enhancer? =

AI Story Enhancer is a new feature that allows you to enhance any text in your WordPress posts using AI. Simply select text, provide instructions, and get professional-quality improvements instantly.

= How do I access AI Story Enhancer? =

Navigate to **Posts → All Posts** in your WordPress admin and click **"AI Story Enhancer"** under any post title. This will open the enhancement interface.

= Is AI Story Enhancer free to use? =

Yes! AI Story Enhancer is completely free to use. There are no subscription fees or usage limits for content enhancement.

= What types of improvements can AI Story Enhancer make? =

AI Story Enhancer can improve readability, add details, enhance engagement, improve SEO, make content more conversational, and much more. The AI understands context and provides relevant enhancements.

= Can I enhance multiple sections of the same post? =

Yes! You can enhance as many sections as you want in the same post. Each enhancement is independent and can be saved together.

= Does AI Story Enhancer work with all WordPress themes? =

Yes, AI Story Enhancer is designed to work with all WordPress themes and doesn't interfere with your site's styling.

= How does the smart save system work? =

The save button is automatically disabled when there are no changes. It only enables when you make changes to the content, tags, or meta description, ensuring you only save when necessary.

== Contributing ==

We welcome contributions! Submit issues or pull requests via [GitHub](https://github.com/hmamoun/ai-story-maker).

== Changelog ==

= 2.1.3 =
* **NEW: AI Story Enhancer**
  * Added intelligent text enhancement capabilities
  * Smart text selection and AI-powered improvements
  * One-click access from posts list
  * Free usage with usage tracking
  * Professional-quality content enhancements
  * Seamless WordPress integration
  * Smart save system with change detection
  * Enhanced tags and SEO management
  * Modern, responsive interface

= 2.1.2 =
* **Maintenance Release**
  * Version bump for WordPress.org deployment
  * Ensured compatibility with latest WordPress version
  * Minor documentation updates

= 2.1.1 =
* **Security & Code Quality Improvements**
  * Fixed all WordPress coding standards violations
  * Enhanced input sanitization and nonce verification
  * Improved debug logging with conditional WP_DEBUG checks
  * Added proper translator comments for internationalization
  * Removed hidden files and debug code from production
* **Website Update**
  * Updated plugin URI to official website: https://www.storymakerplugin.com/
* **Bug Fixes**
  * Resolved linting errors across all admin files
  * Fixed set_time_limit() usage warnings with proper documentation
  * Enhanced security for form data processing

= 2.1.0 =
* Initial release with core AI story generation features
* Social media integration capabilities
* Analytics dashboard and heatmap visualization
* Prompt editor and subscription management

== License ==

GPLv2 or later. Free for personal and commercial use.

== Support the Project ==

If you find AI Story Maker helpful, you can [buy me a coffee](https://buymeacoffee.com/78vcTEm4i) ☕