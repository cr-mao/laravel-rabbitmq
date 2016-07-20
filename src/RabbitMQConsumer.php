<?php namespace Qianka\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer {

    protected $logger = null;

    protected $broker = null;

    protected $exchange = null;
    protected $queue = null;

    protected $channel = null;

    protected $host = null;
    protected $port = null;
    protected $username = null;
    protected $password = null;
    protected $vhost = null;
    protected $heartbeat_interval = null;

    protected $no_ack = null;
    protected $callback = null;
    protected $exclusive = null;
    protected $consumer_tag = null;

    public function __construct(
        $host, $port, $username, $password,
        $vhost, $heartbeat_interval, $exchange, $queue, $logger) {

        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->heartbeat_interval = $heartbeat_interval;

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
        $this->queue = $queue;

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

    public function declareQueue (
        $durable, $exclusive, $auto_delete) {
        $channel = $this->getChannel();
        $channel->queue_declare(
            $this->queue,
            false, // passive
            $durable,
            $exclusive,
            $auto_delete
        );
    }

    public function bindQueue ($exchange, $routing_key) {
        $chan = $this->getChannel();
        $chan->queue_bind(
            $this->queue,
            $exchange,
            $routing_key
        );
    }

    public function getChannel() {
        if ($this->channel == null) {
            $amqp = $this->broker->getConnection();
            $this->channel = $amqp->channel();
        }
        return $this->channel;
    }

    public function reconnect() {
        try {
            $this->destroy();
        }
        catch (\Exception $e) {
            $this->logger->error($e);
        }

        while (1) {
            try {
                $inst = new RabbitMQBroker(
                    $this->host,
                    $this->port,
                    $this->username,
                    $this->password,
                    $this->vhost,
                    $this->heartbeat_interval
                );
                $this->broker = $inst;
                return;
            }
            catch (\Exception $e) {
                $this->logger->error($e);
                sleep(1);
            }
        }
    }

    public function consume(
        $no_ack, $exclusive, $callback, $consumer_tag = '')
    {
        $this->no_ack = $no_ack;
        $this->exclusive = $exclusive;
        $this->callback = function ($message) use ($callback) {
            call_user_func_array($callback, array($message));
        };
        $this->consumer_tag = $consumer_tag;
        $this->_consume();
    }

    private function _consume()
    {
        $chan = $this->getChannel();
        $chan->basic_consume(
            $this->queue,
            $this->consumer_tag,
            false, // no local
            $this->no_ack,
            $this->exclusive,
            false, // not wait
            $this->callback
        );
    }

    public function blockingConsume($reconnect = true, $interval = 1)
    {
        $chan = $this->getChannel();
        while (count($chan->callbacks)) {
            try {
                $chan->wait();
            }
            catch(\Exception $e) {
                $this->logger->warning($e);
                if ($reconnect) {
                    $this->logger->warning("begin reconnect");
                    sleep($interval);
                    $this->reconnect();
                    $chan = $this->getChannel();
                    $this->_consume();
                    $this->logger->warning("reconnected");
                    continue;
                }
                throw $e;
            }
        }
    }

    public function destroy() {
        $chan = $this->getChannel();
        $this->channel = null;
        $broker = $this->broker;
        $this->broker = null;

        $chan->close();
        $broker->getConnection()->close();
    }
}
