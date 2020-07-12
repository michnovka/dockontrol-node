<?php


function DoAction($channel, $action, $duration = 0, $pause = 0){
    switch($action){
        case 'ON':
                $output = `sudo /var/www/html/Relay.sh CH$channel ON`;
                break;
        case 'OFF':
                $output = `sudo /var/www/html/Relay.sh CH$channel OFF`;
                break;

        case 'PULSE':
                $duration = intval($duration);
                $output = `sudo /var/www/html/Relay.sh CH$channel ON`;
                $output .= "\nSleeping $duration microseconds...\n";
                usleep($duration);
                $output .= `sudo /var/www/html/Relay.sh CH$channel OFF`;

                break;

        case 'DOUBLECLICK':
                $duration = intval($duration);
                $pause = intval($pause);
                $output = `sudo /var/www/html/Relay.sh CH$channel ON`;
                $output .= "\nSleeping $duration microseconds...\n";
                usleep($duration);
                $output .= `sudo /var/www/html/Relay.sh CH$channel OFF`;
                $output .= "\nSleeping $pause microseconds...\n";
                usleep($pause);
                $output .= `sudo /var/www/html/Relay.sh CH$channel ON`;
                $output .= "\nSleeping $duration microseconds...\n";
                usleep($duration);
                $output .= `sudo /var/www/html/Relay.sh CH$channel OFF`;

                break;

        default:   
                $output = "Unknown action";
    }

    return $output;
}
 

