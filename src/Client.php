<?php

namespace DiSy\Client;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Client
{
    protected $connection;
    protected $channel;
    protected $requests = [];

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
        $this->channel = $connection->channel();

        // Declare 2 channels
        $queueName = 'client';
        $this->channel->queue_declare($queueName, false, false, false, false);

        $queueName = 'server';
        $this->channel->queue_declare($queueName, false, false, false, false);

        // Setup listener
        $consumerTag = '';
        $res = $this->channel->basic_consume(
            'client',
            $consumerTag,
            false /* no_local */,
            false /* no_ack */,
            false /* exclusive */,
            false /* nowait */,
            [$this, 'onMessage']
        );
    }

    public function publish($data)
    {
        $this->requests[$data['requestId']] = $data;

        $json = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        $msg = new AMQPMessage($json);

        $exchangeName = ''; // direct queue
        $routingKey = 'server';

        //echo "Publishing" . PHP_EOL;
        //echo $json . PHP_EOL;
        $this->channel->basic_publish($msg, $exchangeName, $routingKey);

    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function onMessage($msg)
    {
        //echo " [x] Received ", $msg->body, "\n";

        $data = json_decode($msg->body, true);
        $requestId = $data['requestId'];
        $this->requests[$requestId]['response'] = $data;

        $msg->delivery_info['channel']->
            basic_ack($msg->delivery_info['delivery_tag']);
        //echo " [x] Done", "\n";
    }

    public function ping()
    {
        $requestId = rand(10000000, 99999999);
        $data = [
            'type' => 'ping',
            'requestId' => $requestId,
            'createdAt' => date('Y-m-d H:i:s')
        ];
        $this->publish($data);

        return $this->getResponse($requestId);
    }

    public function getResponse($requestId)
    {
        $timeout = 10;

        while(!isset($this->requests[$requestId]['response'])) {
            // echo "Waiting... for $requestId" . PHP_EOL;
            $this->channel->wait(
                null, /* allowed_methods */
                false, /* non-blocking */
                $timeout /* timeout in seconds */
            );
        }
        return $this->requests[$requestId]['response'];
    }

    public function query($query)
    {
        $requestId = rand(10000000, 99999999);

        // Send query
        $data = [
            'type' => 'query',
            'requestId' => $requestId,
            'createdAt' => date('Y-m-d H:i:s'),
            'query' => $query
        ];

        $this->publish($data);

        return $this->getResponse($requestId);
    }


    public function getFile($filename)
    {
        $requestId = rand(10000000, 99999999);

        // Send query
        $data = [
            'type' => 'getFile',
            'requestId' => $requestId,
            'createdAt' => date('Y-m-d H:i:s'),
            'filename' => $filename
        ];

        $this->publish($data);

        return $this->getResponse($requestId);
    }


    public function disconnect()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
