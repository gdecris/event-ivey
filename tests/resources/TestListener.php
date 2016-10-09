<?php

class TestListener extends \Ivey\Events\EventListener
{
    public static $should_queue = true;
    public static $data = [];

    public function fire($payload)
    {
        self::$data = $payload;
    }
}