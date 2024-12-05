<?php

function gpioExecute(int $relay, bool $state): void
{
    $relayToGPIOPinMap = [
        1 => 5,
        2 => 6,
        3 => 13,
        4 => 16,
        5 => 19,
        6 => 20,
        7 => 21,
        8 => 26,
    ];

    $gpioPin = $relayToGPIOPinMap[$relay] ?? null;

    if(empty($gpioPin)){
        throw new Exception("Relay must be between 1 and 8");
    }

    // Define GPIO chip
    $gpioChip = getenv('GPIO_DEVICE');
    $gpioAction = $state ? 0 : 1;

    $command = "gpioset $gpioChip $gpioPin=$gpioAction";
    exec($command . ' 2>&1', $output, $return_var);

    if ($return_var !== 0) {
        throw new Exception('Error running command: ' . implode("\n", $output));
    }
}
