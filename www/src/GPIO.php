<?php

namespace DockontrolNode;

use Exception;

class GPIO
{
    public const array RELAY_TO_GPIO_PIN_MAP = [
        'WAVESHARE_8_PIN' => [
            1 => 5,
            2 => 6,
            3 => 13,
            4 => 16,
            5 => 19,
            6 => 20,
            7 => 21,
            8 => 26,
        ],
        'WAVESHARE_3_PIN' => [
            1 => 26,
            2 => 20,
            3 => 21,
        ],
    ];

    /**
     * @throws Exception
     */
    public function __construct(private readonly string $relayBoardType)
    {
        if(!array_key_exists($this->relayBoardType, self::RELAY_TO_GPIO_PIN_MAP)){
            throw new Exception("Relay board type not supported");
        }
    }

    /**
     * @throws Exception
     */
    private function gpioExecute(int $relay, bool $state): void
    {
        $gpioPin = self::RELAY_TO_GPIO_PIN_MAP[$this->relayBoardType][$relay] ?? null;

        if(empty($gpioPin)){
            throw new Exception("Invalid relay channel ".$relay);
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
    public function on(int $relay): void
    {
        $this->gpioExecute($relay, true);
    }


    /**
     * @throws Exception
     */
    public function off(int $relay): void
    {
        $this->gpioExecute($relay, false);
    }


    /**
     * @param int $duration value in microseconds
     * @throws Exception
     */
    public function pulse(int $relay, int $duration = 300000): void
    {
        $this->on($relay);
        usleep($duration);
        $this->off($relay);
    }


    /**
     * @param int $duration value in microseconds
     * @param int $delay value in microseconds
     * @throws Exception
     */
    public function doubleClick(int $relay, int $duration = 300000, int $delay = 300000): void
    {
        $this->pulse($relay, $duration);
        usleep($delay);
        $this->pulse($relay, $duration);
    }
}
