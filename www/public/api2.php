<?php

require __DIR__ . '/../vendor/autoload.php';

use DockontrolNode\GPIO;
use DockontrolNode\API;

function APISignedError($message, $code): never
{
    $apiPrivKey = getenv('API_PRIVATE_KEY');
    API::performDockontrolNodeAPIResponse(
        $apiPrivKey,
        $_SERVER['HTTP_X_API_TIMESTAMP'],
        $_SERVER['HTTP_X_API_SIGNATURE'],
        ['status' => 'error', 'code' => $code, 'message' => $message],
        403
    );
}

function APIError($message, $code): never
{
    header('Content-type: application/json');
    echo json_encode(['status' => 'error', 'code' => $code, 'message' => $message]);
    exit;
}

$_SECRET = getenv('API_PRIVATE_KEY');

if(empty($_SECRET)) {
    APIError("No API private key configured.", 403);
}

// TODO: verify the request

$response = [];

switch($_POST['action'] ?? ''){
    case 'version':
        $response['status'] = 'ok';
        $response['version'] = '2.0';
        $response['kernel_version'] = trim(`uname -a`);
        $response['os_version'] = trim(`cat /etc/debian_version`);
        $response['device'] = trim(`cat /sys/firmware/devicetree/base/model`);
        $response['uptime'] = round(trim(`awk '{print $1}' /proc/uptime`));
        break;
}

API::performDockontrolNodeAPIResponse(
    $_SECRET,
    $_SERVER['HTTP_X_API_TIMESTAMP'],
    $_SERVER['HTTP_X_API_SIGNATURE'],
    $response
);