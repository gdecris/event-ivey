<?php

class FailingListener extends \Ivey\Events\EventListener
{
    public static $should_queue = true;
    public static $data = [];
    public static $failed_message = '';

    public function fire($payload)
    {
        self::$data = $payload;
        throw new \Exception("Failed job");
    }

    public function failed()
    {
        self::$failed_message = 'message failed';
    }
}