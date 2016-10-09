<?php


use Ivey\Events\EventDispatcher;
use Ivey\Queues\Adapters\MemoryQueue;

class EventDispatchTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function setup()
    {
        $queue = new MemoryQueue();
        $this->dispatcher = new EventDispatcher($queue);

    }

    public function test_it_can_listen()
    {
        $payload_data = [];
        $event_fired = '';
        $this->dispatcher->listen('foo', function ($payload) use (&$payload_data, &$event_fired) {
            $event_fired = $this->event_name;
            $payload_data = $payload;

        });

        $this->dispatcher->fire('foo', ['test' => 'data']);

        $this->assertEquals('foo', $event_fired);
        $this->assertArrayHasKey('test', $payload_data);
    }
}