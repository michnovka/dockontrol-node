<?php

namespace DockontrolNode;

use Exception;

class GPIO
{
    public const array RELAY_TO_GPIO_PIN_MAP = [
            1 => 5,
            2 => 6,
            3 => 13,
            4 => 16,
            5 => 19,
            6 => 20,
            7 => 21,
            8 => 26,
        ];

    /**
     * @throws Exception
     */
    private static function gpioExecute(int $relay, bool $state): void
    {

        $gpioPin = self::RELAY_TO_GPIO_PIN_MAP[$relay] ?? null;

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


    /**
     * @throws Exception
     */
    public static function on(int $relay): void
    {
        self::gpioExecute($relay, true);
    }


    /**
     * @throws Exception
     */
    public static function off(int $relay): void
    {
        self::gpioExecute($relay, false);
    }


    /**
     * @throws Exception
     */
    public static function pulse(int $relay, int $duration = 300000): void
    {
        self::on($relay);
        usleep($duration);
        self::off($relay);
    }


    /**
     * @throws Exception
     */
    public static function doubleClick(int $relay, int $duration = 300000, int $delay = 300000): void
    {
        self::pulse($relay, $duration);
        usleep($delay);
        self::pulse($relay, $duration);
    }
}
