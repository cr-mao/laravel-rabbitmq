<?php
namespace Qianka\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQPublisher
{

    protected $logger = null;

    protected $broker = null;
    protected $channel = null;

    public function __construct(
        $host, $port, $username, $password,
        $vhost, $heartbeat_interval, $logger)
    {
        $inst = new RabbitMQBroker(
            $host,
            $port,
            $username,
            $password,
            $vhost,
            $heartbeat_interval
        );
        $this->broker = $inst;
        $this->logger = $logger;
    }

    public function getChannel()
    {
        if ($this->channel == null) {
            $amqp = $this->broker->getConnection();
            $this->channel = $amqp->channel();
        }
        return $this->channel;
    }

    public function sendMessage($body, $exchange, $routingKey, $encode = true)
    {
        $payload = $body;
        if ($encode) $payload = json_encode($body);
        $channel = $this->getChannel();
        $message = new AMQPMessage($payload, array(
            "content_type"  => "text/plain",
            "delivery_mode" => 2
        ));
        $channel->basic_publish($message, $exchange, $routingKey);
    }

    /**
     * 生成delay MQ
     * @param $body
     * @param $exchange
     * @param $routingKey
     * @param $delayTime
     * @param bool $encode
     */
    public function sendDelayMessage($body, $exchange, $routingKey, $delayTime, $encode = true)
    {
        $payload = $body;
        if ($encode) $payload = json_encode($body);
        $channel = $this->getChannel();
        $channel->exchange_declare(
            $exchange,
            'x-delayed-message',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(
                ["x-delayed-type" => "topic"]
            )
        );
        $channel->queue_declare(
            $routingKey,
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(
                ["x-delayed-type" => "topic"]
            )
        );
        $channel->queue_bind($routingKey, $exchange, $routingKey);
        $headers = new AMQPTable(array("x-delay" => $delayTime * 1000));
        $message = new AMQPMessage($payload, array('delivery_mode' => 2));
        $message->set('application_headers', $headers);
        $channel->basic_publish($message, $exchange, $routingKey);
    }

    public function destroy()
    {
        $chan = $this->getChannel();
        $chan->close();
        $this->channel = null;
        $this->broker->getConnection()->close();
        $this->broker = null;
    }
}