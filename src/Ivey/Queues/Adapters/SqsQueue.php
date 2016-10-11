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
            'key' => $this->env('AWS_ACCESS_KEY_ID'),
            'secret' => $this->env('AWS_SECRET_ACCESS_KEY')
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
        $this->client->sendMessage([
            'MessageBody' => serialize($payload),
            'QueueUrl' => $this->env('SQS_QUEUE_URL')
        ]);

        return $this;
    }

    /**
     * @param string $queue
     * @return mixed
     */
    public function pull($queue)
    {
        $message = $this->client->receiveMessage([
            'QueueUrl' => $this->env('SQS_QUEUE_URL')
        ]);

        return unserialize($message['Messages'][0]['Body']);
    }

    /**
     * @param string $queue
     * @return array
     */
    public function all($queue)
    {
        return ["SQS adapter does not support all messages"];
    }

    /**
     * @param $varname
     * @param null $default
     * @return null|string
     */
    private function env($varname, $default = null)
    {
        if ( $value = getenv($varname) ) {
            return $value;
        }

        return $default;
    }
}