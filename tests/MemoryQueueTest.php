<?php


use Ivey\Queues\Adapters\MemoryQueue;

class MemoryQueueTest extends PHPUnit_Framework_TestCase
{

    public $queue;

    public function setup()
    {
        $this->queue = new MemoryQueue();

        $this->queue->setNamespace('tests')
            ->push('foo.bar', 'baz')
            ->push('foo.bar', 'biz');

    }

    public function test_it_can_use_dot_notation()
    {
        $this->assertEquals('baz', $this->queue->pull('foo.bar'));
        $this->assertEquals('biz', $this->queue->pull('foo.bar'));

        $this->assertCount(0, $this->queue->all('foo.bar'));
    }

    public function test_it_can_get_all()
    {
        $this->assertContains('baz', $this->queue->all('foo.bar'));
    }
}