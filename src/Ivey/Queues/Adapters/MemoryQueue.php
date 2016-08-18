<?php
/**
 * Created by PhpStorm.
 * User: gdecris
 * Date: 8/17/16
 * Time: 9:02 PM
 */

namespace Ivey\Queues\Adapters;


use Ivey\Queues\Contracts\QueueContract;

class MemoryQueue implements QueueContract
{

    protected $queue = [];
    protected $namespace;

    /**
     * @return $this
     */
    public function boot()
    {
        $this->queue = [];
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        if ( !isset($this->queue[$this->namespace]) ) {
            $this->queue[$this->namespace] = [];
        }

        return $this;
    }

    /**
     * @param string $queue
     * @param $payload
     * @return $this
     */
    public function push($queue, $payload)
    {
        if ( $this->namespace ) {
            $position =& $this->queue[$this->namespace];
        } else {
            $position =& $this->queue;
        }

        foreach ( explode('.', $queue) as $key ) {
            if ( !isset($position[$key]) ) {
                $position[$key] = [];
            }
            $position =& $position[$key];
        }

        $position[] = $payload;

        return $this;
    }

    public function &getValue($queue)
    {
        if ( $this->namespace ) {
            $position =& $this->queue[$this->namespace];
        } else {
            $position =& $this->queue;
        }

        foreach ( explode('.', $queue) as $key ) {
            if ( !isset($position[$key]) ) {
                return null;
            }

            $position =& $position[$key];
        }

        return $position;
    }

    /**
     * @param string $queue
     * @return mixed
     */
    public function pull($queue)
    {

        return array_shift($this->getValue($queue));
    }

    /**
     * @param string $queue
     * @return array
     */
    public function all($queue)
    {
        return $this->getValue($queue);
    }
}