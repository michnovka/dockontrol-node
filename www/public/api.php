<?php

require __DIR__ . '/../vendor/autoload.php';

use DockontrolNode\GPIO;
// legacy API script
// TODO: remove when all nodes are upgraded to v2

require_once(dirname(__FILE__) . '/gpio_lib.php');

function APIError($message, $code): never
{
    echo json_encode(array('status' => 'error', 'code' => $code, 'message' => $message));
    exit;
}

$_SECRET = getenv('LEGACY_API_SECRET');

if(empty($_SECRET)) {
    APIError("No API secret configured.", 403);
}

if($_GET['secret'] != $_SECRET){
    APIError("Authentication error", 403);
}

if (($_GET['action'] ?? '') == 'version') {
    $response = array();
    $response['status'] = 'ok';
    $response['version'] = '2.0';
    $response['kernel_version'] = trim(`uname -a`);
    $response['os_version'] = trim(`cat /etc/debian_version`);
    $response['device'] = trim(`cat /sys/firmware/devicetree/base/model`);
    $response['uptime'] = round(trim(`awk '{print $1}' /proc/uptime`));

    header('Content-type: application/json');
    echo json_encode($response);
    exit;
}


$channel = intval($_GET['channel'] ?? '');

if($channel > 8 || $channel < 1){
    APIError("Invalid channel. Min 1, max 8", 1);
}

if(!in_array($_GET['action'] ?? '', ['ON', 'OFF'])){
    APIError("Invalid action", 1);
}

$reply = array();

try {
    GPIO::gpioExecute($channel, $_GET['action'] == 'ON');
    $reply['status'] = 'ok';
    $reply['message'] = 'Relay '.$channel.' '.$_GET['action'];
}catch(Exception $e){
    APIError($e->getMessage(), $e->getCode());
}

header('Content-type: application/json');
echo json_encode($reply);
exit;
