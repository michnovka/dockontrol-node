<?php

set_time_limit(0);

define('DOCKONTROL_NODE_VERSION', 0.4);


if(php_sapi_name() === 'cli'){
	if($argv[1] == '--version'){
		echo DOCKONTROL_NODE_VERSION;
		exit;
	}
}

require_once(dirname(__FILE__).'/libs/api_libs.php');
require_once(dirname(__FILE__).'/config/API_SECRET.php');

/** @var string $_SECRET defined in config/API_SECRET.php */

if($_GET['secret'] != $_SECRET){
	APIError("Authentication error", 403);
	exit;
}

if($_GET['action'] == 'version'){
	$response = array();
	$response['status'] = 'ok';
	$response['version'] = DOCKONTROL_NODE_VERSION;
	$response['kernel_version'] = trim(`uname -a`);
	$response['os_version'] = trim(`cat /etc/debian_version`);
	$response['device'] = trim(`cat /sys/firmware/devicetree/base/model`);
	$response['uptime'] = round(trim(`awk '{print $1}' /proc/uptime`));

	echo json_encode($response);
	exit;
}elseif($_GET['action'] == 'update'){
	$relay_location = dirname(__FILE__).'/Relay.sh';
	`sudo $relay_location GITUPDATE`;

	$response = array();
	$response['status'] = 'ok';
	$response['old_version'] = DOCKONTROL_NODE_VERSION;
	$this_script = __FILE__;

	$response['new_version'] = `php $this_script --version`;

	echo json_encode($response);
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


