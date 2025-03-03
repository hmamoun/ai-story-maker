for each post, the toal token and the api request id is saved as pst meta data
TODO: get picture from pexels in addition to unsplash
TODO set the auther of the storeis in the settings


instruction
register in open ai and get an API key, save it in the story maker admin page
register in unsplash and get an API key and secret

add a prompt using the example here, (always include that it should mention the source)

{
    "default_settings": {
        "model": "gpt-4o-mini",
        "max_tokens": "1500",
        "timeout": "30",
        "system_content": "You are an expert writer specializing in immigration topics. Return articles in JSON format. The response must strictly follow this structure: { \"title\": \"Article Title\", \"content\": \"Full article content...\", \"excerpt\": \"A short summary of the article...\", \"references\": [ {\"title\": \"Source 1\", \"link\": \"https:\/\/example.com\/source1\"}, {\"title\": \"Source 2\", \"link\": \"https:\/\/example.com\/source2\"} ] }"
    },
    "prompts": [
        {
            "text": "You are an expert writer specializing in immigration topics. Search the internet for the latest news on Canadian immigration policies and generate a well-structured, SEO-optimized article. Ensure the article is engaging, fact-based, and up to date. Provide a clear and compelling title.",
            "category": "Policies",
            "active": "0"
        },
        {
            "text": "Write an article summarizing the latest statistics on Canadian immigration, covering permanent residency, temporary visas, and refugee intake. Ensure readability by structuring the content effectively and integrating insights from official data sources.",
            "max_tokens": "1200",
            "category": "Statistics",
            "active": "0"
        },
        {
            "text": "Write an engaging article with fun facts about a city in Canada. Use simple but captivating language. Within the article, insert a placeholder in the following format {img_unsplash:keyword1,keyword2,keyword3} using the most relevant keywords for fetching related images from Unsplash. we need at least 2 photos in the body of the article, and one as a heading image",
            "max_tokens": "1200",
            "category": "Fun Facts",
            "active": "1"
        }
    ]
}

Instruct ChatGPT to insert the picture tag