<?php


function DoAction($channel, $action, $duration = 0, $pause = 0){
	$relay_location = dirname(dirname(__FILE__).'/../Relay.sh').'/Relay.sh';
    switch($action){
        case 'ON':
                $output = `sudo $relay_location CH$channel ON`;
                break;
        case 'OFF':
                $output = `sudo $relay_location CH$channel OFF`;
                break;

        case 'PULSE':
                $duration = intval($duration);
                $output = `sudo $relay_location CH$channel ON`;
                $output .= "\nSleeping $duration microseconds...\n";
                usleep($duration);
                $output .= `sudo $relay_location CH$channel OFF`;

                break;

        case 'DOUBLECLICK':
                $duration = intval($duration);
                $pause = intval($pause);
                $output = `sudo $relay_location CH$channel ON`;
                $output .= "\nSleeping $duration microseconds...\n";
                usleep($duration);
                $output .= `sudo $relay_location CH$channel OFF`;
                $output .= "\nSleeping $pause microseconds...\n";
                usleep($pause);
                $output .= `sudo $relay_location CH$channel ON`;
                $output .= "\nSleeping $duration microseconds...\n";
                usleep($duration);
                $output .= `sudo $relay_location CH$channel OFF`;

                break;

        default:   
                $output = "Unknown action";
    }

    return $output;
}
 

