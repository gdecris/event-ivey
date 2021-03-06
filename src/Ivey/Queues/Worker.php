<?php

namespace Ivey\Queues;


use Ivey\Events\EventDispatcher;
use Ivey\Events\EventListener;
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
     * @var \Closure
     */
    protected $output_adapter;

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
        $this->dispatcher->setRetryLimit($tries);

        return $this;
    }

    /**
     * Set a closure to receive output
     *
     * @param \Closure $closure
     * @return $this
     */
    public function setOutputAdapter(\Closure $closure)
    {
        $this->output_adapter = $closure;

        return $this;
    }

    /**
     * Runs jobs in queue then sleeps
     */
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
            $this->sendOutput("No jobs in queue");
            return;
        }

        $this->sendOutput("Running next job in queue");

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
    private function jobFailed($job, \Exception $exception)
    {
        $this->sendOutput($exception->getMessage());

        // See if the listener has a failed method that we can notify of the failure
        if ( property_exists($exception, 'listener') && $exception->listener instanceof EventListener ) {
            if ( method_exists($exception->listener, 'failed') ) {
                $this->sendOutput('Sending failed info to listeners failed method');
                call_user_func_array([$exception->listener, 'failed'], [compact('job', 'exception')]);
            }
        }

        // Dispatch a failed job event
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

    /**
     * @param $message
     */
    private function sendOutput($message)
    {
        if ( !$this->output_adapter ) {
            echo $message, "\n";
            return;
        }

        call_user_func_array($this->output_adapter, [$message]);
    }
}