# WP AI StoryMaker

## 📌 Overview
**WP AI StoryMaker** is a WordPress plugin that generates AI-powered stories and fetches relevant images automatically. It integrates with OpenAI for text generation and Unsplash for high-quality images, offering a seamless content creation experience.

## 🔹 Features
✔ **AI-Generated Stories** – Automatically generate unique WordPress posts.  
✔ **Fetch AI-Generated Images** – Pulls relevant images from Unsplash.  
✔ **Admin Dashboard** – Manage and generate AI stories from an easy-to-use interface.  
✔ **Custom Story Scroller** – Display AI-generated stories dynamically.  
✔ **Logging System** – Tracks generated stories and errors for easy debugging.  
✔ **Auto Model Attribution** – The program automatically adds the AI model name at the end of each article to avoid confusion with original news.  

## 🔧 Installation & Setup
1. Upload the plugin files to the `/wp-content/plugins/wp-ai-storymaker/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **AI StoryMaker Settings** in the admin panel.
4. **Create a developer account with OpenAI** ([sign up here](https://platform.openai.com/signup)) to obtain an API key.
5. **Create a free account with Unsplash** ([sign up here](https://unsplash.com/join)) to get API access for fetching images.
6. Configure the API keys in the plugin settings.
7. Generate AI stories and let the plugin auto-fetch images.

## 📜 Usage
- **Manually Generate AI Stories:** Go to **AI StoryMaker > Generate Story** in the WordPress Admin.
- **View Logs:** Check AI-generated content and errors in the **Log Management** panel.
- **Fetch Images:** Automatically or manually fetch related images.
- **Enable Story Scroller:** Display AI-generated stories dynamically on your site.

## 📂 Plugin Files & Structure
```
/wp-ai-storymaker
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
 │   └── story-scroller.php           # story scroller feature [short-code] for AI stories
 ├── templates
 │   └── single-ai-story.php         # Template for displaying AI-generated stories
 └── wp-ai-story-maker.php           # Main plugin file
```

## 📜 Guide to Writing a Sample Prompt
The plugin supports structured prompts to generate AI content effectively. Below is a sample JSON configuration with explanations:

### **🔹 Default Settings**
These settings apply to all prompts unless overridden:
```json
{
    "default_settings": {
        "model": "gpt-4o-mini",
        "max_tokens": "1500",
        "timeout": "30",
        "system_content": "You are an expert writer specializing in technology topics. Return articles in JSON format. The response must strictly follow this structure: { \"title\": \"Article Title\", \"content\": \"Full article content...\", \"excerpt\": \"A short summary of the article...\", \"references\": [ {\"title\": \"Source 1\", \"link\": \"https:\/\/example.com\/source1\"}, {\"title\": \"Source 2\", \"link\": \"https:\/\/example.com\/source2\"} ] }"
    }
}
```

### **🔹 Example Prompts**
```json
{
    "prompts": [
        {
            "text": "You are an expert writer specializing in technology. Search the internet for the latest advancements in AI and generate a well-structured, SEO-optimized article. Ensure the article is engaging, fact-based, and up to date. Provide a clear and compelling title.",
            "category": "AI Research",
            "active": "1"
        },
        {
            "text": "Write an article summarizing the latest statistics on cybersecurity breaches, covering affected companies, financial losses, and mitigation strategies. Ensure readability by structuring the content effectively and integrating insights from official data sources.",
            "max_tokens": "1200",
            "category": "Cybersecurity",
            "active": "1"
        },
        {
            "text": "Write an engaging article with fun facts about space exploration. Use simple but captivating language. Within the article, insert a placeholder in the following format {img_unsplash:keyword1,keyword2,keyword3} using the most relevant keywords for fetching related images from Unsplash. We need at least 2 photos in the body of the article, and one as a heading image",
            "max_tokens": "1200",
            "category": "Space Exploration",
            "active": "1"
        }
    ]
}
```

### **🔹 Understanding the Image Placeholder**
The `{img_unsplash:keyword1,keyword2,keyword3}` tag inside the content is used to fetch images dynamically from Unsplash. When the program processes the article:
1. It extracts the keywords inside `{img_unsplash:}`.
2. It queries Unsplash using those keywords.
3. It automatically places the retrieved images in the article content.
4. One image is set as the **featured image**, while at least two more are inserted within the article.

### **🔹 Automatic Model Attribution**
At the end of each AI-generated article, the plugin **automatically adds a note** stating the AI model used (e.g., `Generated with GPT-4o-mini`) to ensure transparency and avoid confusion with original news.

## 💡 TODO (Upcoming Features)
- **Integrate Pexels API for image fetching.**
- **Support for more image sources (e.g., Pixabay, Adobe Stock).**
- **Multi-language support for AI-generated content.**
- **Add an index page for all generated posts.**



## 📬 Contributing
We welcome contributions! Please open an issue or submit a pull request on [GitHub](https://github.com/YOUR-USERNAME/wp-ai-storymaker).

## 📜 License
This project is licensed under the **GPLv2 or later** – free for personal and commercial use.
