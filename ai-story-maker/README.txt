=== AI Story Maker ===
Contributors: hmamoun
Tags: ai,  content creator, blog automation, article generation
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://github.com/hmamoun/ai-story-maker
Author: Hayan Mamoun
Author URI: https://exedotcom.ca

AI-powered WordPress plugin that generates engaging stories, articles, and images using Large Language Models.

== Description ==

**AI Story Maker** is a powerful AI-driven WordPress plugin that generates unique, engaging stories and articles with intelligently matched images. It uses OpenAI for content creation and Unsplash for visuals, empowering bloggers, marketers, educators, and creatives to scale content creation with ease.

You can manage a library of prompts, auto-generate or review AI-generated posts, and control whether content is saved as draft or published directly. Includes logging, dynamic story display, and attribution features for transparency.

== Features ==

* 🧠 **AI-Generated Content** – Instantly generate original stories and articles via OpenAI’s GPT models.
* 🖼️ **Smart Image Integration** – Automatically pulls royalty-free visuals from Unsplash using dynamic keyword placeholders.
* 🛠️ **Prompt Management** – Build and organize a library of prompts. Activate/deactivate, edit, or attach publishing rules.
* 🧾 **Content Attribution** – Automatically includes the AI model used at the end of each article for clarity and trust.
* 📜 **Logging System** – Tracks every generated item and error.
* 🖥️ **Admin Dashboard** – Simple UI for prompt editing, content generation, and key management.
* 🌀 **Story Scroller** – Visually engaging way to display stories on the frontend.

== Installation ==

1. Upload the plugin to your `/wp-content/plugins/` directory or install via the Plugin Installer.
2. Activate through the ‘Plugins’ menu.
3. Go to **AI Story Maker > Settings** in your admin panel.
4. Get your OpenAI API key from [https://platform.openai.com/signup](https://platform.openai.com/signup).
5. Get your Unsplash API key from [https://unsplash.com/join](https://unsplash.com/join).
6. Enter your API keys in the plugin settings.
7. Start generating AI-powered content!

== Usage ==

- Go to **AI Story Maker > Prompt Editor** to build your prompts.
- Choose whether prompts auto-publish, save as drafts, or require manual review.
- Use `{img_unsplash:keyword1,keyword2}` within your prompts to fetch images.
- View logs and manage past generations under **Log Management**.
- Enable the Story Scroller block/template to showcase content on the frontend.

== Guide to Writing Prompts ==

You can control output with smart prompt design. Try:

- `Write a story about a child discovering a hidden city under the ocean.`
- `Summarize today's top 3 news stories about renewable energy.`
- `Create a blog post about the benefits of meditation for stress reduction.`

Use the `{img_unsplash:}` tag to dynamically insert Unsplash images. The first fetched image becomes the featured image, and others are embedded inline.

== Screenshots ==

1. Admin dashboard for prompt creation and generation controls.
2. Generated story in WordPress post editor.
3. Frontend display with Story Scroller.

== Changelog ==

= 1.0 =
* Initial release with story generation, image integration, logging, and prompt library.

== Upgrade Notice ==

= 1.0 =
First release. Requires API keys for OpenAI and Unsplash to function correctly.

== Frequently Asked Questions ==

= Does this plugin work without an OpenAI key? =
No. An OpenAI key is required to generate content.

= Can I control what gets published? =
Yes. Prompts can be configured to auto-publish, save as drafts, or require manual review.

= Does it support images from other sources? =
Currently only Unsplash. Pexels and others are planned for future releases.

== Privacy ==

This plugin sends prompt data to the OpenAI API to generate content. It also queries the Unsplash API to retrieve relevant public images. No personal data is stored, transmitted, or shared with third parties. Please review the privacy policies of OpenAI and Unsplash for additional details.

== Roadmap ==

* Integrate Pexels and other image providers.
* Add full template support for custom post types.
* Enable bulk generation and scheduling.
* Create a Gutenberg block for in-editor story generation.

== Contributing ==

We welcome contributions! Submit issues or pull requests on [GitHub](https://github.com/hmamoun/ai-story-maker).

== License ==

AI Story Maker is open-source software licensed under the GPLv2 or later.

If you find this plugin helpful, consider supporting it: https://buymeacoffee.com/78vcTEm4i

== Screenshots ==

1. The AI Story Maker settings page where you can enter your OpenAI and Unsplash API keys.
2. The Prompt Editor interface for customizing prompt behavior and controlling publishing rules.
3. A WordPress post containing a generated story with dynamically fetched images and auto-attribution.


├── LICENSE
├── README.txt
├── admin
│   ├── class-ai-story-maker-admin.php
│   ├── class-ai-story-maker-api-keys.php
│   ├── class-ai-story-maker-prompt-editor.php
│   ├── class-ai-story-maker-settings-page.php
│   ├── css
│   │   ├── admin.css
│   │   └── index.php
│   ├── index.php
│   ├── js
│   │   ├── admin.js
│   │   └── index.php
│   └── templates
│       ├── index.php
│       ├── prompt-editor-template.php
│       └── welcome-tab-template.php
├── ai-story-maker.php
├── includes
│   ├── class-ai-story-maker-log-management.php
│   ├── class-ai-story-maker-story-generator.php
│   ├── index.php
│   ├── repository-open-graph.svg
│   └── shortcode-story-scroller.php
├── languages
│   ├── ai-story-maker-es_ES.mo
│   ├── ai-story-maker-es_ES.po
│   ├── ai-story-maker-fr_CA.mo
│   ├── ai-story-maker-fr_CA.po
│   └── ai-story-maker.pot
├── public
│   ├── css
│   │   ├── index.php
│   │   └── aistma-style.css
│   ├── index.php
│   └── single-ai-story.php
└── uninstall.php