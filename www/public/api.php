<?php

require __DIR__ . '/../vendor/autoload.php';

use DockontrolNode\GPIO;
// legacy API script
// TODO: remove when all nodes are upgraded to v2

function APIError($message, $code): never
{
    header('Content-type: application/json');
    echo json_encode(array('status' => 'error', 'code' => $code, 'message' => $message));
    exit;
}

$_SECRET = getenv('LEGACY_API_SECRET');

if(empty($_SECRET)) {
    APIError("No API secret configured.", 403);
}

if(($_GET['secret'] ?? '') != $_SECRET){
    APIError("Authentication error", 403);
}

if (($_GET['action'] ?? '') == 'version') {
    $response = array();
    $response['status'] = 'ok';
    $response['version'] = getenv('DOCKONTROL_NODE_VERSION') ?? '';
    $response['kernel_version'] = getenv('KERNEL_VERSION') ?? '';
    $response['os_version'] = getenv('OS_VERSION') ?? '';
    $response['docker_version'] = getenv('DOCKER_VERSION') ?? '';
    $response['device'] = getenv('DEVICE') ?? '';
    $response['uptime'] = round(trim(`awk '{print $1}' /proc/uptime`));

    header('Content-type: application/json');
    echo json_encode($response);
    exit;
}


$channel = intval($_GET['channel'] ?? '');

if($channel > 8 || $channel < 1){
    APIError("Invalid channel. Min 1, max 8", 1);
}

if(!in_array($_GET['action'] ?? '', ['ON', 'OFF', 'PULSE', 'DOUBLECLICK'])){
    APIError("Invalid action", 1);
}

$reply = array();

try {
    $relayBoardType = getenv('RELAY_BOARD_TYPE');

    if(empty($relayBoardType)){
        APIError("Relay board type not configured", 1);
    }

    $gpio = new GPIO($relayBoardType);

    switch ($_GET['action']) {
        case 'ON':
            $gpio->on($channel);
            break;
        case 'OFF':
            $gpio->off($channel);
            break;
        case 'PULSE':
            $duration = intval($_GET['duration'] ?? 0);
            if(!$duration){
                APIError("Invalid duration for PULSE", 2);
            }
            $gpio->pulse($channel, $duration);
            break;

        case 'DOUBLECLICK':
            $duration = intval($_GET['duration'] ?? 0);
            $pause = intval($_GET['pause'] ?? 0);

            if(!$duration){
                APIError("Invalid duration for PULSE", 2);
            }
            if(!$pause){
                APIError("Invalid pause for PULSE", 2);
            }

            $gpio->doubleClick($channel, $duration, $pause);
            break;

    }

    $reply['status'] = 'ok';
    $reply['message'] = 'Relay '.$channel.' '.$_GET['action'];
}catch(Exception $e){
    APIError($e->getMessage(), $e->getCode());
}

header('Content-type: application/json');
echo json_encode($reply);
exit;
