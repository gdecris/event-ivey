<?php

namespace Ivey\Queues\Contracts;

interface QueueContract {
	/**
	 * @return $this
	 */
	public function boot();
	
	/**
	 * @param string $namespace
	 * @return $this
	 */
	public function setNamespace($namespace);

	/**
	 * @param string $queue
	 * @param $payload
	 * @return $this
	 */
	public function push($queue, $payload);

	/**
	 * @param string $queue
	 * @return mixed
	 */
	public function pull($queue);

	/**
	 * @param string $queue
	 * @return array
	 */
	public function all($queue);

    /**
     * Clears all messages in the queue
     *
     * @param $queue
     * @return mixed
     */
	public function purge($queue);
}
