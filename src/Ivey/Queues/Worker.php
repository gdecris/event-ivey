<?php

namespace Ivey\Queues;


use Ivey\Events\EventDispatcher;
use Ivey\Events\Exceptions\EventListenerDidNotRun;
use Ivey\Events\Exceptions\EventListenerNotCallable;

/**
 * Class Worker
 *
 * @property EventDispatcher dispatcher
 * @package Ivey\Queues
 */
class Worker
{

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var int
     */
    protected $sleep = 5;

    /**
     * @var int
     */
    protected $tries = 5;

    /**
     * Worker constructor.
     *
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param int $sleep
     * @return $this
     */
    public function setSleep($sleep)
    {
        $this->sleep = $sleep;

        return $this;
    }

    /**
     * @param int $tries
     * @return $this
     */
    public function setTries($tries)
    {
        $this->tries = $tries;

        return $this;
    }

    public function runDaemon()
    {
        while ( true ) {
            $this->runNextJob();

            $this->sleep();
        }
    }

    /**
     * Retrieves the next job in the queue
     *
     * @return mixed
     */
    public function getNextJob()
    {
        return $this->dispatcher->nextInQueue();
    }

    /**
     * Runs the next job in the queue
     */
    public function runNextJob()
    {
        $job = $this->getNextJob();

        if ( false === $job ) {
            return;
        }

        try {
            $job = $this->extractJobInfo($job);

            $this->dispatcher->fireListener(
                $job['event_name'],
                $job['listener'],
                $job['payload'],
                $job['attempts']
            );
        } catch (EventListenerDidNotRun $e) {
            $this->jobFailed($job, $e);
        } catch (EventListenerNotCallable $e) {
            $this->jobFailed($job, $e);
        }
    }

    /**
     * @param array $job
     * @return array
     */
    private function extractJobInfo(array $job) {
        $data = [];
        $data['event_name'] = $this->arrayGet($job, 'event_name', 'default');
        $data['listener'] = $this->arrayGet($job, 'listener', null);
        $data['payload'] = $this->arrayGet($job, 'payload', []);
        $data['attempts'] = $this->arrayGet($job, 'attempts', 0);
        return $data;
    }

    /**
     * Sleep for the set amount of seconds
     */
    private function sleep()
    {
        sleep($this->sleep);
    }

    /**
     * For failed jobs fire the failed.job event
     *
     * @param $job
     * @param $exception
     */
    private function jobFailed($job, $exception)
    {
        $this->dispatcher->fire(
            'failed.job',
            compact('job', 'exception')
        );
    }

    /**
     * Gets key from array with default if not set
     *
     * @param array $arr
     * @param $key
     * @param null $default
     * @return null
     */
    private function arrayGet(array $arr, $key, $default = null)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

}