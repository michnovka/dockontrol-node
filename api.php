<?php

set_time_limit(0);

define('DOCKONTROL_NODE_VERSION', 0.1);

require_once(dirname(__FILE__).'/libs/api_libs.php');
require_once(dirname(__FILE__).'/config/API_SECRET.php');

/** @var string $_SECRET defined in config/API_SECRET.php */

if($_GET['secret'] != $_SECRET){
	APIError("Authentication error", 403);
	exit;
}

if($_GET['action'] == 'version'){
	echo json_encode(array('status'=>'ok', 'version' => DOCKONTROL_NODE_VERSION));
	exit;
}

function APIError($message, $code){
        echo json_encode(array('status' => 'error', 'code' => $code, 'message' => $message));
}

$channel = intval($_GET['channel']);

if($channel > 8 || $channel < 1){
        APIError("Invalid channel. Min 1, max 8", 1);
        exit;
}

$output = DoAction($channel, $_GET['action'], $_GET['duration'], $_GET['pause']);

$reply = array();

$reply['status'] = 'ok';
$reply['output'] = $output;

echo json_encode($reply);
exit;


