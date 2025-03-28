AI Story Maker
 * Plugin Name: AI Story Maker
 * Plugin URI: https://github.com/hmamoun/ai-story-maker
 * Description: AI-powered WordPress plugin that generates engaging stories, articles, and images using Large Language Models.
 * Version: 1.0
 * Author: Hayan Mamoun
 * Author URI: https://exedotcom.ca
 * Email: hmamoun@exedotcom.ca
 * Email2: hmamoun@gmail.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Tested up to: 6.7.2
 * Requires PHP: 7.4
 * Stable tag: 1.0
 * Contributors: hmamoun
 */
 
# AI Story Maker

## 📌 Overview
**AI Story Maker** is a WordPress plugin that generates AI-powered stories and fetches relevant images automatically. It integrates with OpenAI for text generation and Unsplash for high-quality images, offering a seamless content creation experience.

## 🔹 Features
✔ **AI-Generated Stories** – Automatically generate unique WordPress posts.  
✔ **Fetch AI-Generated Images** – Pulls relevant images from Unsplash.  
✔ **Admin Dashboard** – Manage and generate AI stories from an easy-to-use interface.  
✔ **Custom Story Scroller** – Display AI-generated stories dynamically.  
✔ **Logging System** – Tracks generated stories and errors for easy debugging.  
✔ **Auto Model Attribution** – The program automatically adds the AI model name at the end of each article to avoid confusion with original news.  

## 🔧 Installation & Setup
1. Upload the plugin files to the `/wp-content/plugins/ai-Story Maker/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **AI Story Maker Settings** in the admin panel.
4. **Create a developer account with OpenAI** ([sign up here](https://platform.openai.com/signup)) to obtain an API key.
5. **Create a free account with Unsplash** ([sign up here](https://unsplash.com/join)) to get API access for fetching images.
6. Configure the API keys in the plugin settings.
7. Generate AI stories and let the plugin auto-fetch images.

## 📜 Usage
- **Manually Generate AI Stories:** Go to **AI Story Maker > Generate Story** in the WordPress Admin.
- **View Logs:** Check AI-generated content and errors in the **Log Management** panel.
- **Fetch Images:** Automatically or manually fetch related images.
- **Enable Story Scroller:** Display AI-generated stories dynamically on your site.

## 📂 Plugin Files & Structure
```
/ai-Story Maker
 ├── LICENSE                        # GPLv2 or later license
 ├── README.md                      # Plugin documentation
 ├── assets
 │   └── story-style.css             # Styles for AI-generated stories
 ├── includes
 │   ├── admin-page.php              # Admin dashboard functionality
 │   ├── generate-story.php          # AI story generation logic
 │   ├── get-photos-pexels.php       # Fetch photos from Pexels (TODO: Not yet implemented)
 │   ├── get-photos-unsplash.php     # Fetch photos from Unsplash API
 │   ├── log-management.php          # Logging system for generated content
 │   └── story-scroller.php           # Story scroller feature [short-code] for AI stories
 ├── templates
 │   └── single-ai-story.php         # Template for displaying AI-generated stories
 └── ai-story-maker.php           # Main plugin file
```

## 📜 Guide to Writing a Sample Prompt
The plugin supports structured prompts to generate AI content effectively. Below is a sample JSON configuration with explanations:

### **🔹 Understanding the Image Placeholder**
The `{img_unsplash:keyword1,keyword2,keyword3}` tag inside the content is used to fetch images dynamically from Unsplash. When the program processes the article:
1. It extracts the keywords inside `{img_unsplash:}`.
2. It queries Unsplash using those keywords.
3. It automatically places the retrieved images in the article content.
4. One image is set as the **featured image**, while at least two more are inserted within the article.

### **🔹 Automatic Model Attribution**
At the end of each AI-generated article, the plugin **automatically adds a note** stating the AI model used (e.g., `Generated with GPT-4o-mini`) to ensure transparency and avoid confusion with original news.

## 📜 Frequently Asked Questions
**Q: How do I configure API keys?**
A: Navigate to **AI Story Maker Settings** and enter your OpenAI and Unsplash API keys.

**Q: Can I customize the article format?**
A: Yes, you can modify the system prompt to change the output structure.

## 📜 Changelog
### 1.0
- Initial release with AI-generated content and image fetching.

## 📜 Upgrade Notice
### 1.0
- First version released. Ensure API keys are configured correctly.

## 📜 Screenshots
_No screenshots available yet._

## 💡 TODO (Upcoming Features)
- **Integrate Pexels API for image fetching.**
- **Support for more image sources (e.g., Pixabay, Adobe Stock).**
- **Add an index page for all generated posts.**

## 📬 Contributing
We welcome contributions! Please open an issue or submit a pull request on [GitHub](https://github.com/hmamoun/ai-story-maker).

## 📜 License
This project is licensed under the **GPLv2 or later** – free for personal and commercial use.

## 📌 Donate
If you find this plugin useful, consider supporting future development: https://buymeacoffee.com/78vcTEm4i (#).
