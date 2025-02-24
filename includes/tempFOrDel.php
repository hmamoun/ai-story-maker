<?php


$apikey = "8XhVnXlhKUfNVYeI0T9OMMDH5NlzazFpkhpQG3KNZtxC2g26bNEmYjDW";

// Search query
$query = 'nature'; // Change this to your desired search term
$perPage = 10; // Number of results per page

// API endpoint
$url = "https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page=" . $perPage;

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $apiKey
]);

// Execute the request
$response = curl_exec($ch);
error_log('Response: ' . $response);
// Check for errors
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    exit;
}

// Close cURL
curl_close($ch);

// Decode the JSON response
$data = json_decode($response, true);

// Check if the response contains photos
if (isset($data['photos'])) {
    foreach ($data['photos'] as $photo) {
        // Display image details
        echo '<div style="margin: 20px; padding: 10px; border: 1px solid #ccc;">';
        echo '<img src="' . $photo['src']['medium'] . '" alt="' . $photo['alt'] . '" style="max-width: 100%;">';
        echo '<p>Photographer: ' . $photo['photographer'] . '</p>';
        echo '<p>Photo URL: <a href="' . $photo['url'] . '" target="_blank">View on Pexels</a></p>';
        echo '</div>';
    }
} else {
    echo 'No photos found.';
}
?>

curl -H "8XhVnXlhKUfNVYeI0T9OMMDH5NlzazFpkhpQG3KNZtxC2g26bNEmYjDW" \
  "https://api.pexels.com/v1/search?query=nature&per_page=1"

  curl -H "8XhVnXlhKUfNVYeI0T9OMMDH5NlzazFpkhpQG3KNZtxC2g26bNEmYjDW" \
   https://api.pexels.com/v1/curated?page=2&per_page=40

   curl -H "8XhVnXlhKUfNVYeI0T9OMMDH5NlzazFpkhpQG3KNZtxC2g26bNEmYjDW" \
  "https://api.pexels.com/v1/search?query=nature&per_page=1"


  curl -H "8XhVnXlhKUfNVYeI0T9OMMDH5NlzazFpkhpQG3KNZtxC2g26bNEmYjDW" \
  "https://api.pexels.com/videos/search?query=nature&per_page=1"
