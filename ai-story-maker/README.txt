=== AI Story Maker ===
Contributors: hmamoun
Tags: ai, content creator, blog automation, article generation
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://github.com/hmamoun/ai-story-maker
Author: Hayan Mamoun
Author URI: https://exedotcom.ca

AI-powered WordPress plugin that generates engaging stories, articles, and images using Large Language Models.

== Description ==

AI Story Maker is a powerful WordPress plugin that generates unique, engaging stories and articles with intelligently matched images. It uses OpenAI for content creation and Unsplash for visuals, empowering bloggers, marketers, educators, and creatives to scale content creation with ease.

You can manage a library of prompts, auto-generate or review AI-generated posts, and control whether content is saved as a draft or published directly. Includes logging, dynamic story display, and attribution features.

== Features ==

- Generate original stories and articles using OpenAI’s GPT models.
- Automatically fetch royalty-free visuals from Unsplash.
- Build and manage a prompt library with publishing rules.
- Automatically include model attribution at the end of each article.
- Track generation activity and errors with a log system.
- Admin interface for editing prompts, managing API keys, and running generations.
- Story Scroller display for frontend story showcasing.

== Installation ==

1. Upload the plugin to your `/wp-content/plugins/` directory or install via the Plugin Installer.
2. Activate the plugin from the Plugins menu in WordPress.
3. Go to "AI Story Maker > Settings" in your admin dashboard.
4. Get your OpenAI API key from https://platform.openai.com/signup
5. Get your Unsplash API key from https://unsplash.com/join
6. Enter your API keys in the plugin settings.
7. Begin generating AI-powered content.

== Usage ==

- Use the Prompt Editor to build and organize prompts.
- Choose whether content is published directly or saved as drafts.
- Use `{img_unsplash:keyword1,keyword2}` in prompts to dynamically insert images.
- View the generation log from the Log tab.
- Add the Story Scroller shortcode to any page or widget area to display content.

== Prompt Examples ==

Example prompts to try:

- Write a story about a child discovering a hidden city under the ocean.
- Summarize today's top 3 news stories about renewable energy.
- Create a blog post about the benefits of meditation for stress reduction.

Use `{img_unsplash:}` to dynamically insert Unsplash images. The first image becomes the featured image, others appear inline.

== Screenshots ==

1. The AI Story Maker settings page for API key input.
2. The Prompt Editor for managing prompts and behavior.
3. A generated post with dynamic images and auto attribution.

== Changelog ==

= 1.0 =
* Initial release with AI story generation, image integration, prompt management, and logging.

== Upgrade Notice ==

= 1.0 =
Initial version. Requires valid OpenAI and Unsplash API keys.

== Frequently Asked Questions ==

= Does this plugin work without an OpenAI key? =
No. An OpenAI API key is required to generate content.

= Can I control whether content is published or saved as drafts? =
Yes. Each prompt can be configured to auto-publish or require review.

= Does it support other image sources? =
Currently only Unsplash is supported. Additional sources like Pexels are planned.

== Privacy ==

This plugin sends prompt data to the OpenAI API to generate content. It also queries the Unsplash API to retrieve relevant images. No personal data is stored, transmitted, or shared. Refer to OpenAI and Unsplash privacy policies for more details.

== Roadmap ==

- Add support for additional image providers (e.g. Pexels).
- Enable bulk post generation and scheduling.
- Provide template support for custom post types.
- Add Gutenberg block for story creation in the editor.

== Contributing ==

We welcome contributions. Submit issues or pull requests at https://github.com/hmamoun/ai-story-maker

== License ==

AI Story Maker is open-source software licensed under the GPLv2 or later.

== External Services Disclosure ==

This plugin connects to the following third-party APIs:

1. OpenAI API (https://platform.openai.com/)

Used to generate content such as titles, full articles, summaries, and references. Sent data includes prompt text and optional recent post excerpts. No personal data is shared.

- Endpoint: https://api.openai.com/v1/chat/completions // this is the API endpoint addres, cannot be visited directly
- Terms: https://openai.com/policies/terms-of-use
- Privacy: https://openai.com/policies/privacy-policy

2. Unsplash API (https://unsplash.com/developers)

Used to fetch royalty-free images using keyword placeholders. Sent data includes search keywords only. No personal or account data is shared.

- Endpoint: https://api.unsplash.com/search/photos // this is the API endpoint addres, cannot be visited directly
- Terms: https://unsplash.com/terms
- Privacy: https://unsplash.com/privacy

This plugin requires your own API keys. By using it, you agree to the terms and privacy policies of the listed services.
