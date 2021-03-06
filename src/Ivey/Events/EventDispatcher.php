<?php

namespace Ivey\Events;

use Illuminate\Container\Container;
use Ivey\Events\Exceptions\EventListenerDidNotRun;
use Ivey\Events\Exceptions\EventListenerNotCallable;
use Exception;
use Closure;
use Error;
use Ivey\Queues\Contracts\QueueContract;
use ReflectionClass;

/**
 * Class EventDispatcher
 *
 * @package Ivey\Events
 */
class EventDispatcher
{

	protected $listeners = [];

	/**
	 * @var RedisQueue $queue
	 */
	protected $queue;
	protected $queue_name = 'ivey:queue';
	protected $retry_limit = 5;

	/**
	 * EventDispatcher constructor.
	 * @param QueueContract $queue
	 */
	public function __construct(QueueContract $queue) {
		$this->queue = $queue;
        $this->container = new Container();
		$this->queue->boot();
	}

	/**
	 * @param $event_name
	 * @param $callable
	 */
	public function listen($event_name, $callable) {
		$this->validateCallable($callable);

		if ( !isset($this->listeners[$event_name]) ) {
			$this->listeners[$event_name] = [];
		}

		if ( $this->isClosure($callable) ) {
			$bind = new EventStub();
			$bind->event_name = $event_name;
			$callable = $callable->bindTo($bind);
		}

		$this->listeners[$event_name][] = $callable;
	}

	/**
	 * @param $event_name
	 * @param $payload
	 */
	public function fire($event_name, $payload) {
		if ( isset($this->listeners[$event_name]) ) {
			foreach ( $this->listeners[$event_name] as $listener ) {
				$this->executeListener($event_name, $listener, $payload);
			}
		}
	}

	/**
	 * Returns all items in the queue
	 */
	public function all()
    {
		return $this->queue->all($this->queue_name);
	}

	/**
	 * @return mixed
	 */
	public function nextInQueue()
    {
	    if ( $raw = $this->queue->pull($this->queue_name) ) {
	        return unserialize($raw);
        }

        return $raw;
	}

    /**
     * Purges all messages in the queue
     */
	public function purgeQueue()
    {
        $this->queue->purge($this->queue_name);
    }

	/**
	 * @param $callable
	 * @return bool
	 * @throws EventListenerNotCallable
	 */
	private function validateCallable($callable) {
		if ( $this->isEventListener($callable) || is_callable($callable) ) {
			return true;
		}

		throw new EventListenerNotCallable('Non callable listener supplied to addListener()');
	}

	/**
	 * @param $closure
	 * @return bool
	 */
	private function isClosure($closure) {
		return is_object($closure) && $closure instanceof Closure;
	}

	/**
	 * @param      $listener
	 * @param null $reflection
	 * @return bool
	 */
	private function isEventListener($listener, &$reflection = null) {
		if ( !$this->isClosure($listener) && is_string($listener) ) {
			// Read into the object to determine if it should queue
			$reflection = new ReflectionClass($listener);
			if ( $reflection->isSubclassOf(EventListener::class)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * This will execute a listener
	 * It will automatically queue listeners that have the property should_queue
	 *
	 * @param $event_name
	 * @param $listener
	 * @param $payload
	 * @return bool|void
	 */
	private function executeListener($event_name, &$listener, &$payload) {
		// If we have a closure then we can use it is firing now
		// otherwise we need to check if it's queueable
		if ( $this->isEventListener($listener, $reflection) ) {
			// Push the listener to the queue if it is set to queue
			if ( $reflection->getStaticPropertyValue('should_queue') ) {
				$this->enqueue($event_name, $listener, $payload);
				return true;
			}
		}

		return $this->fireListener($event_name, $listener, $payload);
	}

	/**
	 * Fires off a listener
	 *
	 * @param string    $event_name
	 * @param mixed     $listener
	 * @param mixed     $payload_data
	 * @param int       $attempts
	 * @return bool
	 * @throws Exception
	 */
	public function fireListener($event_name, $listener, $payload_data, $attempts = 0) {
		$queued = false;
		$listener_instance = null;

		try {
			// Fire the event listener
			if ( $this->isEventListener($listener, $reflection) ) {
				$queued = $reflection->getStaticPropertyValue('should_queue');
				$listener_instance = $this->container->make($listener);
                $listener_instance->setEventName($event_name)->fire($payload_data);
			} else {
				call_user_func_array($listener, [$payload_data]);
			}

			return true;
		} catch ( \Exception $e ) {
			// Catch Exceptions in the Listener
			if ( $queued ) {
				$this->requeue($event_name, $listener, $payload_data, $attempts, $e, $listener_instance);
				return true;
			}

			$listener_error = "EventListener exception thrown and is not configured be re-queued: [event_name: {$event_name}]";
			$this->didNotRun($listener_error, $e, $listener_instance);
		} catch ( \Error $e ) {
			// Catch Errors in the listener
			if ( $queued ) {
				$this->requeue($event_name, $listener, $payload_data, $attempts, $e, $listener_instance);
				return true;
			}

			$listener_error = "EventListener has ERRORS and is not configured be re-queued: [event_name: {$event_name}]";
            $this->didNotRun($listener_error, $e, $listener_instance);
		}

		return false;
	}

	/**
	 * @param $event_name
	 * @param $listener
	 * @param $payload
	 * @param $attempts
	 */
	private function enqueue($event_name, $listener, $payload, $attempts = 0) {
		$this->queue->push($this->queue_name, serialize([
			'event_name' => $event_name,
			'listener' => $listener,
			'payload' => $payload,
			'attempts' => $attempts
		]));
	}

    /**
     * Re-queue the event
     *
     * @param $event_name
     * @param $listener
     * @param $payload
     * @param $attempts
     * @param $e
     * @param $listener_instance
     * @throws EventListenerDidNotRun
     */
	private function requeue($event_name, $listener, $payload, $attempts, &$e, $listener_instance) {
		$attempts++;
		if ( $attempts >= $this->retry_limit ) {
		    $this->didNotRun('Listener has reached the max retry attempts and can not be requeued', $e, $listener_instance);
		}

		$this->enqueue($event_name, $listener, $payload, ($attempts + 1));
	}

	/**
	 * @param int $retry_limit
	 */
	public function setRetryLimit($retry_limit) {
		$this->retry_limit = $retry_limit;
	}

    /**
     * @param $message
     * @param $e
     * @param $listener_instance
     * @throws EventListenerDidNotRun
     */
    private function didNotRun($message, $e, $listener_instance)
    {
        $exception = new EventListenerDidNotRun($message, 0, $e);
        $exception->listener = $listener_instance;
        throw $exception;
    }
}
