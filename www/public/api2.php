<?php

require __DIR__ . '/../vendor/autoload.php';

use DockontrolNode\GPIO;
use DockontrolNode\API;

function APISignedError(string $message, int $httpCode): never
{
    $apiPrivKey = getenv('API_PRIVATE_KEY');
    API::performDockontrolNodeAPIResponse(
        $apiPrivKey,
        $_SERVER['HTTP_X_API_TIMESTAMP'],
        $_SERVER['HTTP_X_API_SIGNATURE'],
        ['status' => 'error', 'message' => $message],
        $httpCode
    );
}

function APIError(string $message, int $httpCode): never
{
    header('Content-type: application/json');
    http_response_code($httpCode);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

$_API_PRIVATE_KEY = getenv('API_PRIVATE_KEY');
$_API_PUBLIC_KEY = getenv('API_PUBLIC_KEY');

if(empty($_API_PRIVATE_KEY)) {
    APIError("No API private key configured.", 500);
}

if(empty($_API_PUBLIC_KEY)) {
    APIError("No API public key configured.", 500);
}

if(empty($_SERVER['HTTP_X_API_KEY']) || empty($_SERVER['HTTP_X_API_TIMESTAMP']) || empty($_SERVER['HTTP_X_API_SIGNATURE'])) {
    APIError("Required headers missing", 403);
}

if(!API::verifyDockontrolNodeAPIRequest(
    $_API_PUBLIC_KEY,
    $_API_PRIVATE_KEY,
    $_SERVER['HTTP_X_API_KEY'],
    $_SERVER['HTTP_X_API_TIMESTAMP'],
    $_SERVER['HTTP_X_API_SIGNATURE'],
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['PATH_INFO'],
    file_get_contents('php://input'),
)){
    APIError("Unauthorized", 403);
}

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
    $_API_PRIVATE_KEY,
    $_SERVER['HTTP_X_API_TIMESTAMP'],
    $_SERVER['HTTP_X_API_SIGNATURE'],
    $response
);
