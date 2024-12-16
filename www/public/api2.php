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
    APIError("No API private key configured.", 501);
}

if(empty($_API_PUBLIC_KEY)) {
    APIError("No API public key configured.", 501);
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
    $_SERVER['DOCUMENT_URI'],
    file_get_contents('php://input'),
)){
    APIError("Unauthorized", 403);
}

$response = [];

switch($_POST['action'] ?? ''){
    case 'version':
        $response['status'] = 'ok';
        $response['version'] = getenv('DOCKONTROL_NODE_VERSION') ?? '';
        $response['kernel_version'] = getenv('KERNEL_VERSION') ?? '';
        $response['os_version'] = getenv('OS_VERSION') ?? '';
        $response['docker_version'] = getenv('DOCKER_VERSION') ?? '';
        $response['device'] = getenv('DEVICE') ?? '';
        $response['uptime'] = round(trim(`awk '{print $1}' /proc/uptime`));
        break;

    case 'action':
      
        try {
            switch ($_POST['type'] ?? '') {
                case 'relay':
                    $relayBoardType = getenv('RELAY_BOARD_TYPE');

                    if(empty($relayBoardType)){
                        APISignedError("Relay board type not configured", 400);
                    }

                    $gpio = new GPIO($relayBoardType);

                    if (!isset($_POST['channel'])) {
                        APISignedError("No channel", 400);
                    }

                    $gpio->pulse($_POST['channel']);
                    $response['status'] = 'ok';
                    $response['message'] = 'Relay ' . $_POST['channel'] . ' PULSE';
                    break;
                case 'openwebnet':
                    $_OPENWEBNET_IP = getenv('OPENWEBNET_IP');
                    $_OPENWEBNET_PORT = intval(getenv('OPENWEBNET_PORT'));
                    $_OPENWEBNET_PASSWORD = getenv('OPENWEBNET_PASSWORD');

                    if(empty($_OPENWEBNET_IP) || empty($_OPENWEBNET_PORT) || empty($_OPENWEBNET_PASSWORD)){
                        APISignedError("No OpenWebNet configuration", 501);
                    }

                    // channel can be 0
                    if (!isset($_POST['channel']) || !is_numeric($_POST['channel'])) {
                        APISignedError("No channel", 400);
                    }

                    $own = new OpenWebNet($_OPENWEBNET_IP, $_OPENWEBNET_PORT, $_OPENWEBNET_PASSWORD, OpenWebNetDebuggingLevel::NONE);
                    $own = $own->GetDoorLockInstance();
                    $own->OpenDoor(intval($_POST['channel']));

                    $response['status'] = 'ok';
                    $response['message'] = 'OWN door ' . $_POST['channel'] . ' OPEN';

                    break;
                default:
                    APISignedError("Unknown payload type", 400);
            }
        }catch (Throwable $e){
            APISignedError($e->getMessage(), 422);
        }

        break;
    case 'camera':

        if(empty($_POST['host']) || empty($_POST['channel'])) {
            APISignedError("Missing camera info", 400);
        }

        $protocol = $_POST['protocol'] ?? 'http';

        $width = intval($_POST['width'] ?? 1920);
        $height = intval($_POST['height'] ?? 1080);

        $streamUrl = $protocol.'://'.$_POST['host'].'/ISAPI/Streaming/channels/'.$_POST['channel'].'/picture?videoResolutionWidth='.$width.'&videoResolutionHeight='.$height;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $streamUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(!empty($_POST['login'])) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($ch, CURLOPT_USERPWD, $_POST['login']);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $photoData = curl_exec($ch);

        if(empty($photoData)) {
            APISignedError("cURL error #".curl_errno($ch)." - ".curl_error($ch), 400);
        }

        if(!empty($_POST['return_raw'])) {
            header('Content-type: image/jpeg');
            echo $photoData;
            exit;
        }

        $response['status'] = 'ok';
        $response['photo_data'] = $photoData;

        break;
}

API::performDockontrolNodeAPIResponse(
    $_API_PRIVATE_KEY,
    $_SERVER['HTTP_X_API_TIMESTAMP'],
    $_SERVER['HTTP_X_API_SIGNATURE'],
    $response
);
