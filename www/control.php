<?php

if (isset($_POST['action']) && isset($_POST['relay']) && isset($_POST['gpioPin'])) {
    $action = $_POST['action'];
    $relay = intval($_POST['relay']);
    $gpioPin = intval($_POST['gpioPin']);

    setcookie('gpio_pin_'. $gpioPin, $action == 'on');

    // Define GPIO chip
    $gpioChip = 'gpiochip0';

    if ($action === 'on') {
        $gpioAction = 0;
    } elseif ($action === 'off') {
        $gpioAction = 1;
    } else {
        echo 'Invalid action';
        exit;
    }

    $command = "gpioset $gpioChip $gpioPin=$gpioAction";
    exec($command . ' 2>&1', $output, $return_var);

    if ($return_var !== 0) {
        echo 'Error running command: ' . implode("\n", $output);
    } else {
        echo "Relay $relay turned " . ($action === 'on' ? "on" : "off");
    }
} else {
    echo "No action or relay number provided";
}
