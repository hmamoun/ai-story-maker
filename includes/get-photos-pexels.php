<?php
/*
Replaces the following place holder in a text with a real image from unsplash
{img_unsplash:keyword1,keyword2,keyword3}


function replace_image_placeholders_pexels($article_content) {
    return preg_replace_callback('/\{img_pexels:([a-zA-Z0-9,_ ]+)\}/', function ($matches) {
        $keywords = explode(',', $matches[1]);
        $image = fetch_pexels_image($keywords);
    
        if ($image) {
            return $image;
        } else {
            return ''; // Remove placeholder if no image is found
        }
    }, $article_content);
}

function fetch_pexels_image($keywords) {
    $api_key = get_option('pexels_api_key');

    $query = implode(',', $keywords);
    $response = wp_remote_get("https://api.pexels.com/v1/search?query=" . urlencode($query), [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return ''; // Return empty if there's an error
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['photos'])) {
        return ''; // Return empty if no images found
    }

    $image_index = array_rand($data['photos']); // Pick a random image

    if (!empty($data['photos'][$image_index]['src']['small'])) {
        $url = $data['photos'][$image_index]['src']['small'];
        $credits = $data['photos'][$image_index]['photographer'] . ' by pexels.com';
        $ret = '<figure><img src="' . esc_url($url) . '" alt="' . esc_attr(implode(' ', $keywords)) . '" /><figcaption>'.$credits.'</figcaption></figure>';

        return $ret;
    }

    return ''; // Return empty if no images found


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
    $article = "This is a fun123 {img_pexels:Quebec City} fact about Canada. ";
    $updated_article = replace_image_placeholders_pexels($article);
    echo $updated_article; // Return article with real images
    exit();
*/