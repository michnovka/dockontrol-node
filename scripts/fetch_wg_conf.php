<?php


function callApi(string $url, string $apiPubKey, string $apiPrivKey, string $method, array $data): array
{
    $timestamp = time();

    // Parse the URL to get the path
    $parsedUrl = parse_url($url);
    if (!isset($parsedUrl['path'])) {
        throw new Exception('Invalid URL: Path is missing.');
    }
    $path = $parsedUrl['path'];

    // Prepare the body and query string based on the HTTP method
    if ($method === 'GET') {
        $queryString = http_build_query($data);
        if ($queryString) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . $queryString;
        }
        $body = '';
    } elseif ($method === 'POST') {
        $body = http_build_query($data);
    } else {
        throw new Exception('Unsupported HTTP method: ' . $method);
    }

    // Create the data string for signature
    $dataString = $timestamp . $method . $path . $body;

    // Compute the signature
    $signature = hash_hmac('sha256', $dataString, $apiPrivKey);

    // Set headers
    $headers = [
        'X-API-KEY: ' . $apiPubKey,
        'X-API-SIGNATURE: ' . $signature,
        'X-API-TIMESTAMP: ' . $timestamp,
    ];

    // Set Content-Type header for POST requests
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Handle different HTTP methods
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    } elseif ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    // Set headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the request
    $response = curl_exec($ch);

    // Check for cURL errors
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL error: ' . $error);
    }

    // Get HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL
    curl_close($ch);

    // Check for HTTP errors
    if ($httpCode !== 200) {
        throw new Exception('HTTP error code: ' . $httpCode . ', response: ' . $response);
    }

    // Decode JSON response
    $jsonData = json_decode($response, true);

    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decode error: ' . json_last_error_msg());
    }

    // Return the decoded JSON data
    return $jsonData;
}

$dockontrol_url = getenv('DOCKONTROL_URL');
$api_public_key = getenv('API_PUBLIC_KEY');
$api_private_key = getenv('API_PRIVATE_KEY');

// Prepare the API request
$api_endpoint = $dockontrol_url . '/api/node/get-config';

$data = callApi($api_endpoint, $api_public_key, $api_private_key, 'POST', []);

if (!isset($data['wg_conf'])) {
    echo "Invalid response from API\n";
    exit(1);
}

// Save wg0.conf to the appropriate directory
file_put_contents('/etc/wireguard/wg0.conf', $data['wg_conf']);

echo "WireGuard configuration fetched successfully\n";
exit(0);
