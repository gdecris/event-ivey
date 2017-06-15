<?php

use Ivey\Events\EventDispatcher;
use Ivey\Queues\Adapters\MemoryQueue;
use Ivey\Queues\Worker;

require_once 'resources/TestListener.php';
require_once 'resources/FailingListener.php';

/**
 * @property Worker worker
 */
class WorkerTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $queue = new MemoryQueue();
        $dispatcher = new EventDispatcher($queue);
        $this->worker = new Worker($dispatcher);
    }

    public function test_it_can_extract_job_info()
    {
        $job = $this->worker->getNextJob();

        // It will be false until we add items onto the queue
        $this->assertEquals(false, $job);

        $this->worker->getDispatcher()->listen('foo', TestListener::class);
        $this->worker->getDispatcher()->fire('foo', ['test' => 'data']);

        $job = $this->worker->getNextJob();

        $this->assertArrayHasKey('event_name', $job);
        $this->assertArrayHasKey('listener', $job);
        $this->assertArrayHasKey('payload', $job);
        $this->assertArrayHasKey('attempts', $job);

        $this->assertEquals('foo', $job['event_name']);
        $this->assertEquals(TestListener::class, $job['listener']);
        $this->assertArrayHasKey('test', $job['payload']);
        $this->assertEquals(0, $job['attempts']);

    }

    public function test_it_can_run_queued_job()
    {
        $this->worker->getDispatcher()->listen('foo', TestListener::class);
        $this->worker->getDispatcher()->fire('foo', ['test' => 'run']);

        $this->worker->runNextJob();

        $this->assertEquals('run', TestListener::$data['test']);
    }

    public function test_it_can_fail()
    {
        $this->worker->getDispatcher()->listen('foo', FailingListener::class);
        $this->worker->getDispatcher()->fire('foo', ['test' => 'run']);

        $this->worker->setTries(1)
            ->runNextJob();

        $this->assertNotEmpty(FailingListener::$failed_message);
    }

    public function test_output_adapter()
    {
        $sample = '';
        $this->worker->setOutputAdapter(function ($message) use (&$sample) {
            $sample = $message;
        });

        $this->worker->runNextJob();

        $this->assertNotEmpty($sample);
    }
}