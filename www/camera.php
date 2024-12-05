<?php

// legacy camera script
// TODO: remove when all nodes are upgraded to v2

function fetchCameraPicture(string $stream_url, string $stream_login): bool|string
{

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $stream_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if($stream_login) {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $stream_login);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    return curl_exec($ch);
}

if(empty($_GET['ip']) || empty($_GET['channel']) || empty($_GET['login'])) {
    http_response_code(400);
    echo "Invalid request";
    exit;
}

$photo_data = fetchCameraPicture('http://'.$_GET['ip'].'/ISAPI/Streaming/channels/'.$_GET['channel'].'/picture?videoResolutionWidth=1920&videoResolutionHeight=1080', $_GET['login']);

if(!$photo_data) {
    http_response_code(400);
    echo "Invalid request";
    exit;
}

header('Content-type: image/jpeg');
echo $photo_data;
