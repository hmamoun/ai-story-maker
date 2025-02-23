<?
/*
Replaces the following place holder in a text with a real image from unsplash
{img_unsplash:keyword1,keyword2,keyword3}

*/
function replace_image_placeholders($article_content) {
    return preg_replace_callback('/\{img_unsplash:([a-zA-Z0-9,_ ]+)\}/', function ($matches) {
        $keywords = explode(',', $matches[1]);
        $image = fetch_unsplash_image($keywords);
    
        if ($image) {
            return $image;
        } else {
            return ''; // Remove placeholder if no image is found
        }
    }, $article_content);
}

function fetch_unsplash_image($keywords) {
    $api_key = get_option('unsplash_api_key');

    $query = implode(',', $keywords);
    $url = "https://api.unsplash.com/search/photos?query=" . urlencode($query) . "&client_id=" . $api_key . "&per_page=30&orientation=landscape&quantity=100";
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return ''; // Return empty if there's an error
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $image_index = array_rand($data['results']); // Pick a random image
    if (!empty($data['results'][$image_index]['urls']['small'])) {
        $url = $data['results'][$image_index]['urls']['small'];
        $credits = $data['results'][$image_index]['user']['name'] . ' by unsplash.com';
        $ret = '<figure><img src="' . esc_url($url) . '" alt="' . esc_attr(implode(' ', $keywords)) . '" /><figcaption>'.$credits.'</figcaption></figure>';

        return $ret;

    }

    return ''; // Return empty if no images found
}
// Example usage:
    // $article = "This is a fun {img_unsplash:Quebec City} fact about Canada. ";
    // $updated_article = replace_image_placeholders($article);
    // echo $updated_article; // Return article with real images
    // exit();
