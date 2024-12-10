<?php

require_once '/var/www/html/vendor/autoload.php';

use DockontrolNode\API;

$dockontrol_url = getenv('DOCKONTROL_URL');
$api_public_key = getenv('API_PUBLIC_KEY');
$api_private_key = getenv('API_PRIVATE_KEY');

// Prepare the API request
$api_endpoint = $dockontrol_url . '/api/node/get-config';

try {
    $data = API::callApi($api_endpoint, $api_public_key, $api_private_key, 'POST', []);
} catch (Exception $e) {
    fwrite(STDERR, "Unexpected API error: ".$e->getMessage()."\n");
    exit(1);
}

if (!isset($data['wg_conf'])) {
    fwrite(STDERR, "Invalid response from API - no wg_conf\n");
    exit(1);
}

// Output wg0.conf contents to stdout
echo $data['wg_conf'];

exit(0);
