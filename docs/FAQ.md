# AI Story Maker - Frequently Asked Questions (FAQ)

## Table of Contents
1. [Getting Started](#getting-started)
2. [AI Story Generation](#ai-story-generation)
3. [AI Story Enhancer](#ai-story-enhancer)
4. [Subscriptions & API Keys](#subscriptions--api-keys)
5. [Shortcodes & Display](#shortcodes--display)
6. [Social Media Integration](#social-media-integration)
7. [Analytics & Performance](#analytics--performance)
8. [Troubleshooting](#troubleshooting)
9. [Advanced Usage](#advanced-usage)

---

## Getting Started

### Q: What is AI Story Maker?
**A:** AI Story Maker is a powerful WordPress plugin that uses artificial intelligence (OpenAI) to automatically generate high-quality stories and blog posts. It can also enhance existing content, automatically add images from Unsplash, and publish to social media platforms.

### Q: What are the system requirements?
**A:** 
- WordPress 5.8 or higher
- PHP 7.4 or higher
- Modern web browser with JavaScript enabled
- Active internet connection (for AI generation and image fetching)

### Q: How do I install AI Story Maker?
**A:** You can install it in two ways:
1. **WordPress Plugin Directory**: Go to Plugins > Add New, search for "AI Story Maker", and click Install and Activate
2. **Manual Upload**: Download the ZIP file, go to Plugins > Add New > Upload Plugin, select the ZIP file, and activate

### Q: Is AI Story Maker free?
**A:** The plugin itself is free and open-source (GPLv2 license). However, to generate stories, you need either:
- A subscription plan (which includes API access)
- Your own OpenAI and Unsplash API keys

The AI Story Enhancer feature is completely free to use with no credits required.

### Q: Where do I start after installation?
**A:** After activation:
1. Go to **AI Story Maker** in your WordPress admin menu
2. Choose between subscription or API keys setup
3. Configure your story generation preferences
4. Create your first prompt template
5. Generate your first story!

---

## AI Story Generation

### Q: How does AI story generation work?
**A:** The plugin uses OpenAI's API to generate content based on prompts you create. You define:
- The story topic or theme
- Writing style and tone
- Length and structure
- Categories and tags
- Image placeholders for Unsplash integration

### Q: What is a prompt template?
**A:** A prompt template is a reusable instruction set that tells the AI how to generate content. It includes:
- The main prompt/instructions for the AI
- Categories to assign to generated posts
- Auto-publish settings
- Image placeholders (using `{img_unsplash:keywords}` syntax)

### Q: How do I create effective prompts?
**A:** Tips for effective prompts:
- Be specific about the topic and style
- Include desired length (e.g., "Write a 500-word article about...")
- Specify the target audience
- Add tone instructions (e.g., "Write in a friendly, conversational tone")
- Use image placeholders: `{img_unsplash:mountain landscape}`

### Q: Can I schedule automatic story generation?
**A:** Yes! You can set up automatic generation schedules in the Generation Controls section. Stories can be generated:
- Daily at specific times
- Weekly on specific days
- Based on custom intervals

### Q: How long does it take to generate a story?
**A:** Typically 10-30 seconds per story, depending on:
- Story length
- OpenAI API response time
- Number of images being fetched
- Your server's connection speed

### Q: Can I edit generated stories before publishing?
**A:** Absolutely! All generated stories are created as WordPress draft posts. You can:
- Edit the content in the WordPress editor
- Use AI Story Enhancer to improve sections
- Add or remove images
- Modify categories and tags
- Schedule publication

### Q: What happens if generation fails?
**A:** The plugin logs all errors. Check:
- **AI Story Maker > Logs** for detailed error messages
- Your API key validity (if using your own keys)
- Subscription status (if using subscription)
- Internet connectivity
- OpenAI API status

### Q: Can I generate multiple stories at once?
**A:** Yes! You can generate multiple stories in one batch by:
- Selecting multiple prompt templates
- Using the bulk generation feature
- Setting up scheduled generation

---

## AI Story Enhancer

### Q: What is AI Story Enhancer?
**A:** AI Story Enhancer is a free feature that lets you improve any existing post by selecting text and asking AI to enhance it. It's completely free with no credits required.

### Q: How do I access AI Story Enhancer?
**A:** 
1. Go to **Posts > All Posts** in WordPress
2. Find any post and look for the **"AI Story Enhancer"** link under the post title
3. Click it to open the enhancement interface

### Q: How does text enhancement work?
**A:** 
1. Open AI Story Enhancer for a post
2. Select any text in the content preview
3. Enter instructions (e.g., "Make this more engaging" or "Add more details")
4. Click "Improve" to see AI-powered enhancements
5. Review and accept or modify the suggestions

### Q: What can I enhance?
**A:** You can enhance:
- Post content (any selected text)
- Tags and keywords (with AI suggestions)
- SEO meta descriptions (auto-generated)

### Q: Is AI Story Enhancer free?
**A:** Yes! AI Story Enhancer is completely free to use with no subscription or credits required.

### Q: Can I enhance multiple sections at once?
**A:** You enhance one section at a time, but you can enhance multiple sections sequentially in the same session.

### Q: Does enhancement affect my original content?
**A:** No. The original content remains unchanged until you explicitly save the enhanced version. You can preview all changes before saving.

---

## Subscriptions & API Keys

### Q: What's the difference between subscription and API keys?
**A:** 
- **Subscription**: Pay a monthly fee, get credits included, no need to manage API keys
- **API Keys**: Use your own OpenAI and Unsplash API keys, pay directly to those services

### Q: How do subscriptions work?
**A:** 
1. Choose a subscription plan (free options available)
2. Enter your email and verify it
3. Complete payment (if required)
4. Credits are automatically added to your account
5. Credits are used when generating stories

### Q: How do I set up my own API keys?
**A:** 
1. Go to **AI Story Maker > API Keys**
2. Get your OpenAI API key from https://platform.openai.com/api-keys
3. Get your Unsplash API key from https://unsplash.com/developers
4. Enter both keys in the settings
5. Save and test the connection

### Q: Can I switch between subscription and API keys?
**A:** Yes! You can use either method. If you have both:
- Subscription takes priority for story generation
- Your API keys serve as a backup
- AI Story Enhancer always works regardless

### Q: What happens when I run out of credits?
**A:** 
- If using subscription: You'll need to upgrade or wait for the next billing cycle
- If using API keys: Stories will use your OpenAI account directly (you pay OpenAI)

### Q: How do I check my credit balance?
**A:** Go to **AI Story Maker > Subscriptions** to see:
- Current credit balance
- Subscription status
- Usage history
- Renewal date

### Q: Can I use the free subscription plan?
**A:** Yes! There's a free plan available with limited credits. Check the subscriptions page for current free plan details.

### Q: Are my API keys secure?
**A:** Yes. API keys are stored securely in your WordPress database and are never exposed in the frontend or shared with third parties (except OpenAI/Unsplash when making API calls).

---

## Shortcodes & Display

### Q: What shortcodes are available?
**A:** The plugin provides three main shortcodes:
- `[aistma_posts_gadget]` - Display posts in a grid or list with search/filter
- `[aistma_scroller]` - Sticky scrolling story bar
- `[aistma_adsense]` - AdSense integration

### Q: How do I use the posts gadget shortcode?
**A:** Basic usage:
```
[aistma_posts_gadget]
```

With options:
```
[aistma_posts_gadget posts_per_page="8" layout="grid" show_search="true" show_filters="true"]
```

### Q: What options are available for [aistma_posts_gadget]?
**A:** Common options:
- `posts_per_page` - Number of posts (default: 6)
- `layout` - "grid" or "list"
- `show_search` - "true" or "false"
- `show_filters` - "true" or "false"
- `categories` - Comma-separated category IDs (e.g., "2,5")
- `date_range` - "today", "week", "month", or "year"
- `highlight_new` - "true" or "false"

### Q: How does the scroller shortcode work?
**A:** The `[aistma_scroller]` shortcode creates a sticky, auto-scrolling bar at the bottom of the page showing your latest stories. Simply add it to any page:
```
[aistma_scroller]
```

### Q: Where can I place shortcodes?
**A:** Shortcodes work in:
- Posts and pages (in the editor)
- Widgets (using Text widget)
- Page builders (Elementor, Gutenberg, etc.)
- Theme templates (using `do_shortcode()`)

### Q: Can I customize the appearance?
**A:** Yes! The plugin includes CSS classes you can style:
- Check **AI Story Maker > Settings** for styling options
- Add custom CSS in your theme's style.css
- Use WordPress Customizer for live preview

### Q: Do shortcodes work with caching plugins?
**A:** Yes, but you may need to clear cache after:
- Generating new stories
- Changing shortcode settings
- Updating post categories

---

## Social Media Integration

### Q: Which social media platforms are supported?
**A:** Currently:
- **Facebook Pages** - Full support

Coming soon:
- X (Twitter)
- LinkedIn Company Pages
- Instagram Business Accounts

### Q: How do I connect my Facebook page?
**A:** 
1. Go to **AI Story Maker > Social Media Integration**
2. Click "Add Facebook Account"
3. Follow the OAuth authentication process
4. Select the page(s) you want to connect
5. Test the connection

### Q: How does auto-publishing work?
**A:** 
1. Enable "Auto-Publish New Stories" in Social Media settings
2. Select which accounts should receive auto-published posts
3. When a story is published, it automatically shares to enabled accounts
4. Posts include title, excerpt, link, and hashtags

### Q: Can I customize social media posts?
**A:** Yes! You can:
- Add default hashtags (applied to all posts)
- Use post tags as hashtags (automatic conversion)
- Customize the post format
- Add custom text before/after the link

### Q: Can I publish manually instead of auto-publishing?
**A:** Yes! You can:
- Disable auto-publish globally
- Use bulk actions to publish multiple posts at once
- Publish individual posts from the post editor

### Q: How are hashtags generated?
**A:** Hashtags come from:
1. Post tags (automatically converted: "tech news" → "#technews")
2. Default hashtags (set in Social Media settings)
3. Custom hashtags (added per post)

### Q: Can I connect multiple Facebook pages?
**A:** Yes! You can connect multiple accounts and choose which ones receive each post.

### Q: What if social media publishing fails?
**A:** Check the logs:
- **AI Story Maker > Logs** shows all publishing attempts
- Verify account connections are still valid
- Check API rate limits
- Ensure posts have required content (title, link)

---

## Analytics & Performance

### Q: What analytics are available?
**A:** The plugin tracks:
- Post views and traffic
- Click-through rates
- Engagement heatmaps
- Tag-based performance
- Time-based trends

### Q: How do I view analytics?
**A:** Go to **AI Story Maker > Analytics** to see:
- Traffic heatmaps
- Recent post activity
- Performance metrics
- Detailed logs

### Q: What is a heatmap?
**A:** A heatmap visualizes which posts and tags are getting the most traffic, helping you identify popular content topics.

### Q: Can I export analytics data?
**A:** Currently, analytics are viewable in the dashboard. Export functionality may be added in future updates.

### Q: How accurate is the traffic tracking?
**A:** The plugin tracks:
- Page views (when posts are loaded)
- User interactions
- Referral sources
- All data is logged in real-time

### Q: Does analytics affect site performance?
**A:** No. Analytics tracking is lightweight and asynchronous, so it doesn't slow down your site.

---

## Troubleshooting

### Q: Stories aren't generating. What's wrong?
**A:** Check these common issues:
1. **API Keys**: Verify your OpenAI API key is valid
2. **Subscription**: Check if your subscription is active and has credits
3. **Internet**: Ensure your server can reach OpenAI API
4. **Logs**: Check **AI Story Maker > Logs** for specific error messages
5. **Permissions**: Ensure WordPress can create posts

### Q: Images aren't appearing in stories
**A:** Troubleshooting steps:
1. Verify Unsplash API key is set (if using API keys)
2. Check image placeholder syntax: `{img_unsplash:keywords}`
3. Ensure keywords are descriptive (e.g., "sunset beach" not just "image")
4. Check logs for Unsplash API errors
5. Verify internet connectivity

### Q: Shortcodes aren't displaying
**A:** Common fixes:
1. Clear WordPress cache
2. Clear browser cache
3. Verify shortcode syntax (check for typos)
4. Ensure posts exist to display
5. Check if theme supports shortcodes in that location

### Q: Social media auto-publish isn't working
**A:** Check:
1. Auto-publish is enabled in settings
2. At least one account is connected and enabled
3. Posts are being published (not saved as drafts)
4. Account connections are still valid (re-authenticate if needed)
5. Check logs for specific error messages

### Q: AI Story Enhancer isn't working
**A:** Troubleshooting:
1. Ensure JavaScript is enabled in your browser
2. Check browser console for errors
3. Verify you're selecting text before clicking "Improve"
4. Check internet connectivity
5. Try refreshing the page

### Q: Subscription verification email not received
**A:** 
1. Check spam/junk folder
2. Verify email address is correct
3. Wait a few minutes (emails can be delayed)
4. Check email server logs
5. Try requesting verification code again

### Q: Credits not updating after purchase
**A:** 
1. Wait a few minutes (processing can take time)
2. Refresh the subscriptions page
3. Check payment confirmation email
4. Contact support if issue persists
5. Check logs for payment processing errors

### Q: Plugin conflicts with other plugins
**A:** 
1. Deactivate other plugins one by one to identify conflict
2. Check for JavaScript conflicts (browser console)
3. Verify PHP version compatibility
4. Check WordPress error logs
5. Contact support with conflict details

---

## Advanced Usage

### Q: Can I customize the story generation prompts programmatically?
**A:** Yes! The plugin uses WordPress hooks. You can use filters like:
- `aistma_story_prompt` - Modify prompts before sending to AI
- `aistma_generated_content` - Modify content after generation
- `aistma_post_data` - Modify post data before saving

### Q: Can I integrate with other plugins?
**A:** Yes! The plugin follows WordPress standards and can integrate with:
- SEO plugins (Yoast, RankMath)
- Page builders (Elementor, Beaver Builder)
- Caching plugins (WP Super Cache, W3 Total Cache)
- Form builders (Contact Form 7, Gravity Forms)

### Q: How do I add custom image sources besides Unsplash?
**A:** You can extend the plugin using WordPress filters:
- `aistma_image_sources` - Add custom image providers
- `aistma_fetch_image` - Customize image fetching logic

### Q: Can I generate stories in multiple languages?
**A:** Yes! Include language instructions in your prompts:
- "Write in Spanish..."
- "Create content in French..."
- The AI will generate content in the specified language

### Q: How do I bulk generate stories?
**A:** 
1. Create multiple prompt templates
2. Use the bulk generation feature
3. Or set up scheduled generation for automatic bulk creation

### Q: Can I use custom post types?
**A:** Currently, stories are created as standard WordPress posts. Custom post type support may be added in future updates.

### Q: How do I backup my prompts and settings?
**A:** 
- Prompts are stored in WordPress options
- Use WordPress export/import
- Or use a backup plugin that backs up the database
- Settings are in: `wp_options` table (keys starting with `aistma_`)

### Q: Can developers extend the plugin?
**A:** Absolutely! The plugin is open-source (GPLv2) and includes:
- WordPress action hooks
- WordPress filter hooks
- Well-documented code
- Extensible architecture

### Q: How do I report bugs or request features?
**A:** 
- GitHub Issues: https://github.com/hmamoun/ai-story-maker/issues
- Support: Check the plugin's support page
- Feature requests are welcome!

### Q: Is there a way to test without using credits?
**A:** Yes! Use the AI Story Enhancer feature - it's completely free. You can also test with very short prompts to minimize credit usage.

---

## Additional Resources

### Q: Where can I learn more?
**A:** 
- **Official Website**: https://www.storymakerplugin.com/
- **GitHub Repository**: https://github.com/hmamoun/ai-story-maker
- **Documentation**: Check the docs folder in the plugin
- **Support**: Contact through the plugin's support channels

### Q: Can I contribute to the project?
**A:** Yes! Contributions are welcome:
- Report bugs
- Suggest features
- Submit pull requests
- Improve documentation
- Translate the plugin

### Q: What's coming in future updates?
**A:** Planned features include:
- Full social platform support (X, LinkedIn, Instagram)
- Gutenberg block integration
- Bulk generation improvements
- Enhanced SEO integrations
- Multi-language interface
- Advanced analytics dashboard

---

**Need more help?** Check the plugin logs, WordPress debug logs, or contact support through the official channels.

