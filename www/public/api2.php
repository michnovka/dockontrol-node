<?php

require __DIR__ . '/../vendor/autoload.php';

use DockontrolNode\GPIO;
use DockontrolNode\API;
use Michnovka\OpenWebNet\OpenWebNet;
use Michnovka\OpenWebNet\OpenWebNetDebuggingLevel;

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

    case 'action':
        if(empty($_POST['payload'])){
            APISignedError("No payload", 400);
        }

        $payload = json_decode($_POST['payload'], true);

        if(json_last_error() != JSON_ERROR_NONE){
            APISignedError("Invalid payload", 400);
        }

        try {
            switch ($payload['type'] ?? '') {
                case 'relay':
                    if (!isset($payload['channel'])) {
                        APISignedError("No channel", 400);
                    }

                    GPIO::pulse($payload['channel']);
                    $response['status'] = 'ok';
                    $response['message'] = 'Relay ' . $payload['channel'] . ' ' . $payload['action'];
                    break;
                case 'openwebnet':
                    $_OPENWEBNET_IP = getenv('OPENWEBNET_IP');
                    $_OPENWEBNET_PORT = intval(getenv('OPENWEBNET_PORT'));
                    $_OPENWEBNET_PASSWORD = getenv('OPENWEBNET_PASSWORD');

                    if(empty($_OPENWEBNET_IP) || empty($_OPENWEBNET_PORT) || empty($_OPENWEBNET_PASSWORD)){
                        APISignedError("No OpenWebNet configuration", 400);
                    }

                    // channel can be 0
                    if (!isset($payload['channel']) || !is_numeric($payload['channel'])) {
                        APISignedError("No channel", 400);
                    }

                    $own = new OpenWebNet($_OPENWEBNET_IP, $_OPENWEBNET_PORT, $_OPENWEBNET_PASSWORD, OpenWebNetDebuggingLevel::NONE);
                    $own = $own->GetDoorLockInstance();
                    $own->OpenDoor(intval($payload['channel']));

                    break;
                default:
                    APISignedError("Unknown payload type", 400);
            }
        }catch (Throwable $e){
            APISignedError($e->getMessage(), 500);
        }

        break;
}

API::performDockontrolNodeAPIResponse(
    $_API_PRIVATE_KEY,
    $_SERVER['HTTP_X_API_TIMESTAMP'],
    $_SERVER['HTTP_X_API_SIGNATURE'],
    $response
);
