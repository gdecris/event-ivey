<?php

use Ivey\Events\EventDispatcher;
use Ivey\Queues\Adapters\SqsQueue;
use Ivey\Queues\Worker;


require_once 'resources/TestListener.php';


/**
 * @property EventDispatcher dispatcher
 */
class SqsQueueTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        (new \Dotenv\Dotenv(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR))->load();
        $queue = new SqsQueue();
        $this->dispatcher = new EventDispatcher($queue);
    }

    public function test_it_can_send_messages()
    {
        $this->dispatcher->listen('event.name', TestListener::class);

        $this->dispatcher->fire('event.name', ['sqstest' => 'bar']);

        (new Worker($this->dispatcher))->runNextJob();

        $this->assertEquals('bar', TestListener::$data['sqstest']);
    }
    
// Commenting this out since you can only purge so many times before aws yells
//    public function test_it_can_purge_and_return_false_on_empty_queue()
//    {
//        $this->dispatcher->fire('event.name', ['sqstest' => 'bar']);
//
//        $this->dispatcher->purgeQueue();
//
//        $job = (new Worker($this->dispatcher))->getNextJob();
//
//        $this->assertFalse($job, "Empty queue Job is false");
//    }
}