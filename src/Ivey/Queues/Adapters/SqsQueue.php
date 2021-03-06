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

        $this->setNamespace($this->env('SQS_QUEUE_URL'));
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
            'QueueUrl' => $this->namespace
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
            'QueueUrl' => $this->namespace,
            'MaxNumberOfMessages' => 1
        ]);

        if ( !isset($message['Messages']) ) {
            return false;
        }

        $job = $message['Messages'][0];

        $this->_deleteMessage($job);

        return unserialize($job['Body']);
    }

    /**
     * Deletes a jobs message from the queue
     *
     * @param $job
     */
    private function _deleteMessage($job)
    {
        $this->client->deleteMessage([
            'QueueUrl' => $this->namespace,
            'ReceiptHandle' => $job['ReceiptHandle']
        ]);
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

    /**
     * Clears all messages in the queue
     *
     * @param $queue
     * @return mixed
     */
    public function purge($queue)
    {
        $this->client->purgeQueue([
            'QueueUrl' => $this->namespace
        ]);
    }
}