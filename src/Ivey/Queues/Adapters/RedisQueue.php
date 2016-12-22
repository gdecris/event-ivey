<?php

namespace Ivey\Queues\Adapters;

use Ivey\Queues\Contracts\QueueContract;

class RedisQueue implements QueueContract {
	protected $namespace;
	protected $connection;

	/**
	 * Initialize the cache
	 * @return $this
	 */
	public function boot() {

		$this->connection = cache();
		
		return $this;
	}

	/**
	 * Attach namespace to the queue when applicable
	 * @param $queue
	 * @return string
	 */
	public function q($queue) {
		$prefix = ( $this->namespace ? $this->namespace . ':' : '' );

		return "{$prefix}{$queue}";
	}

	/**
	 * @param string $namespace
	 * @return $this
	 */
	public function setNamespace($namespace) {
		$this->namespace = $namespace;

		return $this;
	}

	/**
	 * @param string $queue
	 * @param        $payload
	 * @return $this
	 */
	public function push($queue, $payload) {
		$this->connection->lpush($this->q($queue), $payload);

		return $this;
	}

	/**
	 * @param string $queue
	 * @return mixed
	 */
	public function pull($queue) {
		$payload = $this->connection->rpop($this->q($queue));

		return $payload;
	}

	/**
	 * @param string $queue
	 * @return array
	 */
	public function all($queue) {
		$raw_payloads = $this->connection->lrange($this->q($queue), 0, -1);
		$total = count($raw_payloads);

		$payloads = [];
		for ( $j = $total - 1; $j >= 0; $j-- ) {
			$payloads[] = $raw_payloads[$j];
		}

		return $payloads;
	}

    /**
     * Clears all messages in the queue
     *
     * @param $queue
     * @return mixed
     */
    public function purge($queue)
    {
        // TODO: Implement purge() method.
    }
}
