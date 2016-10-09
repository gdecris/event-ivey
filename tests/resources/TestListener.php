<?php

class TestListener extends \Ivey\Events\EventListener
{
    public static $should_queue = true;

    public function fire($payload)
    {
        //
    }
}