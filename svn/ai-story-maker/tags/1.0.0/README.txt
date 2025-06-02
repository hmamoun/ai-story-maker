=== AI Story Maker ===
Contributors: hmamoun
Tags: ai, content creation, blog automation, article generation, wordpress ai plugin
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://github.com/hmamoun/ai-story-maker
Author: Hayan Mamoun
Author URI: https://exedotcom.ca

AI-powered WordPress plugin to automatically generate unique stories, articles, and visuals using OpenAI and Unsplash APIs.

== Description ==

**AI Story Maker** helps you instantly generate unique, high-quality WordPress posts using AI and royalty-free images. It integrates with OpenAI for text generation and Unsplash for fetching dynamic images, saving you hours of content creation time.

Whether you're a blogger, marketer, or educator, AI Story Maker helps you build a full content strategy effortlessly.

== Features ==

- **AI-Generated Stories** – Create unique, professional stories and articles using OpenAI.
- **Smart Image Integration** – Fetch dynamic, royalty-free images from Unsplash.
- **Prompt Editor** – Build, customize, and organize your own prompts.
- **Custom Story Scroller** – Display stories dynamically on the frontend.
- **Auto Model Attribution** – Add a model credit note automatically for transparency.
- **Logging System** – Monitor and debug AI generations easily.

== Installation ==

You have two options to install AI Story Maker:

1. **Install from the WordPress Plugin Directory** (Recommended):
   - Go to **Plugins > Add New** in your WordPress dashboard.
   - Search for "**AI Story Maker**".
   - Click **Install Now** and then **Activate**.

*(Note: If the plugin is pending approval, use the manual method below.)*

2. **Install via Uploading ZIP File**:
   - Download the ZIP from [GitHub Repository](https://github.com/hmamoun/ai-story-maker).
   - Go to **Plugins > Add New > Upload Plugin**.
   - Upload the ZIP, install, and activate.

== After Installing ==

1. **Create an OpenAI Account**  
   [Sign up for OpenAI](https://platform.openai.com/signup) and create an API key.  
   _Note: OpenAI offers free trial credits, but usage beyond the free tier may require a paid subscription._

2. **Create an Unsplash Developer Account**  
   [Sign up for Unsplash](https://unsplash.com/join) and create an application to get your API Access Key.  
   _Note: Unsplash's API is free for most use cases, but higher usage or commercial projects may be subject to restrictions or require a special agreement._

3. **Configure the API Keys**  
   - Go to **AI Story Maker > Settings**.
   - Enter your OpenAI and Unsplash API keys.
   - Save your settings.

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

### Fetching Related Photos

- Insert `{img_unsplash:keyword1,keyword2}` inside your prompt text.
- The plugin queries Unsplash and fetches matching royalty-free images.
- The first image becomes the **featured image**; others are placed inline.

== Displaying Generated Stories ==

AI Story Maker saves AI-generated content as regular WordPress posts. 
This means your stories will appear automatically wherever your theme displays posts, such as:

- Blog pages
- Post archives
- Menus and featured content areas

Additionally, the plugin provides a shortcode to display a scrolling bar of stories at the bottom of any page or post.

=== Shortcode: [aistma_scroller] ===

The shortcode [aistma_scroller] displays a dynamic scrolling bar of the latest AI-generated stories.

You can add the shortcode:
- In any WordPress page or post
- Inside a shortcode block
- In a widget area that supports shortcodes
- In a PHP template file using: echo do_shortcode('[aistma_scroller]');

=== How to Use ===

1. Edit a page or post in WordPress.
2. Add a new Shortcode Block (or paste directly).
3. Enter: [aistma_scroller]
4. Save the page.

The scroller adapts to your site's theme styles automatically. Additional CSS customization is possible if needed.

=== Important Notes ===

- It is fully responsive for mobile devices.
- Normal WordPress post listings are not affected.


== Screenshots ==

_(Coming soon)_

== Plugin File Structure ==

ai-story-maker
├── ai-story-maker.php
├── LICENSE
├── README.txt
├── uninstall.php
├── admin
│   ├── class-aistma-admin.php
│   ├── class-aistma-api-keys.php
│   ├── class-aistma-prompt-editor.php
│   ├── class-aistma-settings-page.php
│   ├── index.php
│   ├── css
│   │   ├── admin.css
│   │   └── index.php
│   ├── js
│   │   ├── admin.js
│   │   └── index.php
│   └── templates
│       ├── general-settings-template.php
│       ├── index.php
│       ├── log-table-template.php
│       ├── prompt-editor-template.php
│       └── welcome-tab-template.php
├── includes
│   ├── class-aistma-log-manager.php
│   ├── class-aistma-story-generator.php
│   ├── index.php
│   └── shortcode-story-scroller.php
├── languages
│   ├── ai-story-maker-es_ES.mo
│   ├── ai-story-maker-es_ES.po
│   ├── ai-story-maker-fr_CA.mo
│   ├── ai-story-maker-fr_CA.po
│   └── ai-story-maker.pot
└── public
    ├── index.php
    ├── css
    │   ├── aistma-style.css
    │   └── index.php
    ├── images
    │   └── logo.svg
    └── templates
        ├── aistma-post-template.php
        └── index.php
        
== Guide to Writing Prompts ==

- Example prompt:  
  `Write a news article about the latest trends in clean energy.`

- Add `{img_unsplash:clean energy,renewable}` to fetch relevant images dynamically.

The plugin ensures:
- External images are placed correctly.
- An attribution note about the AI model is automatically added.

== Frequently Asked Questions ==

= How do I configure API keys? =
Go to **AI Story Maker Settings** and enter your OpenAI and Unsplash API keys.

= Can I customize article formats? =
Yes, edit the "General Instructions" field to control structure, tone, and style.

= Can I disable automatic generation? =
Yes, set "Generate New Stories Every" to `0` to disable scheduled stories.

== Changelog ==

= 1.0 =
- Initial release with AI story generation, image integration, logging, and prompt editor.

== Upgrade Notice ==

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

== How AI Story Maker Retrieves General Instructions ==

AI Story Maker dynamically fetches the general AI system instructions from the Exedotcom API Gateway instead of hardcoding them inside the plugin.
	-	The plugin makes a secure HTTP request to retrieve the latest best-practice instructions.
	-	If the request fails or returns no data, a default internal fallback is used to ensure smooth operation.
	-	The retrieved instructions are cached temporarily to minimize remote calls and enhance performance.
	-	Allows future improvements and best practices without needing plugin updates.
	-	Ensures the generated articles continue to follow the latest content structure and SEO guidelines.

Privacy note:
	-	No user personal data is sent.
	-	Only the site URL and server IP address are transmitted for simple tracking and security purposes.
	-	See our API terms of service at https://exedotcom.ca/api-terms (optional link if you plan to add later).
   -  visit this address to see the latest provided instructions: https://exedotcom.ca/wp-json/exaig/v1/aistma-general-instructions


No personal user data is collected or stored.

== Contributing ==

We welcome contributions! Submit issues or pull requests via [GitHub](https://github.com/hmamoun/ai-story-maker).

== License ==

GPLv2 or later. Free for personal and commercial use.

== Support the Project ==

If you find AI Story Maker helpful, you can [buy me a coffee](https://buymeacoffee.com/78vcTEm4i) ☕
