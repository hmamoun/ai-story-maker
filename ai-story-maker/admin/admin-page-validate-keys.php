<?php
/*
 * This plugin is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */
if (!defined('ABSPATH')) exit;
function validate_unsplash_api_key($api_key) {
    // Sanitize input
    $api_key = sanitize_text_field(wp_unslash($api_key));

    // Check format (Unsplash API keys are typically 32-character alphanumeric strings)
    if (!preg_match('/^[a-zA-Z0-9]{32}$/', $api_key)) {
        return false;
    }

    // Test API call to Unsplash
    $url = "https://api.unsplash.com/photos/random?client_id=" . urlencode($api_key);
    $response = wp_remote_get($url, ['timeout' => 5]);

    if (is_wp_error($response)) {
        return false; // Connection error
    }

    $status_code = wp_remote_retrieve_response_code($response);
    
    return $status_code === 200;
}

// // Example usage
// $unsplash_api_key = $_POST['unsplash_api_key'];
// if (validate_unsplash_api_key($unsplash_api_key)) {
//     echo "Valid API Key";
// } else {
//     echo "Invalid API Key";
// }



function validate_openai_api_key($api_key) {
    // Sanitize input
    $api_key = sanitize_text_field(wp_unslash($api_key));
    if ( $api_key == '') {
        return true;
    }


    // Test API call to OpenAI
    $url = "https://api.openai.com/v1/models";
    $response = wp_remote_get($url, [
        'timeout' => 5,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
    ]);
  
    if (is_wp_error($response)) {
        return false; // Connection error
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $model_ids = [];
    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $model) {
            if (isset($model['id'])) {
                $model_ids[] = $model['id'];
            }
        }
        //save modules as session variable
        $_SESSION['model_ids'] = $model_ids;
        return true;
    } else {
        return false; // Connection error
    }
}

// // Example usage
// $openai_api_key = $_POST['openai_api_key'];
// if (validate_openai_api_key($openai_api_key)) {
//     echo "Valid API Key";
// } else {
//     echo "Invalid API Key";
// }
