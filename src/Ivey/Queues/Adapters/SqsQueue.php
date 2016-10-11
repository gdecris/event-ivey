<?php

namespace Ivey\Queues\Adapters;


use Aws\Sqs\SqsClient;
use Ivey\Queues\Contracts\QueueContract;

class SqsQueue implements QueueContract
{

    protected $client;

    protected $namespace = '';

    /**
     * @return $this
     */
    public function boot()
    {
        $this->client = new SqsClient([
            'version' => $this->env('AWS_VERSION', 'latest'),
            'region'  => $this->env('AWS_REGION', 'us-east-1'),
            'credentials' => [
                'key' => $this->env('AWS_ACCESS_KEY_ID'),
                'secret' => $this->env('AWS_SECRET_ACCESS_KEY')
            ]
        ]);
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param string $queue
     * @param $payload
     * @return $this
     */
    public function push($queue, $payload)
    {
        // TODO: Implement push() method.
    }

    /**
     * @param string $queue
     * @return mixed
     */
    public function pull($queue)
    {
        // TODO: Implement pull() method.
    }

    /**
     * @param string $queue
     * @return array
     */
    public function all($queue)
    {
        // TODO: Implement all() method.
    }
}