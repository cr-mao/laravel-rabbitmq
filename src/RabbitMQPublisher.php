<?php namespace Qianka\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher {

    protected $logger = null;

    protected $broker = null;
    protected $exchange = null;
    protected $channel = null;

    public function __construct(
        $host, $port, $username, $password,
        $vhost, $heartbeat_interval, $exchange, $logger) {
        $inst = new RabbitMQBroker(
            $host,
            $port,
            $username,
            $password,
            $vhost,
            $heartbeat_interval
        );
        $this->broker = $inst;
        $this->exchange = $exchange;
        $this->logger = $logger;
    }

    public function declareExchange (
        $type, $durable, $auto_delete, $internal) {
        $channel = $this->getChannel();
        $channel->exchange_declare(
            $this->exchange,
            $type,
            false, // passive
            $durable,
            $auto_delete,
            $internal
        );
    }

    public function getChannel() {
        if ($this->channel == null) {
            $amqp = $this->broker->getConnection();
            $this->channel = $amqp->channel();
        }
        return $this->channel;
    }

    public function sendMessage($body, $encode = true) {
        $payload = $body;
        if ($encode)
            $payload = json_encode($body);

        $channel = $this->getChannel();

        $message = new AMQPMessage($payload, array(
            "content_type" => "text/plain",
            "delivery_mode" => 2
        ));
        $channel->basic_publish($message, $this->exchange);
    }

    public function destroy() {
        $chan = $this->getChannel();
        $chan->close();
        $this->channel = null;
        $this->broker->getConnection()->close();
        $this->broker = null;
    }
}
