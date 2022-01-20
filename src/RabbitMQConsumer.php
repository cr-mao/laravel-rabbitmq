<?php
namespace PayCenter\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer
{

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

    // not implemented yet
    protected $network_recovery = false;
    protected $topology_recovery = false;

    public function __construct(
        $host, $port, $username, $password,
        $vhost, $heartbeat_interval,
        RabbitMQExchange $exchange,
        RabbitMQQueue $queue, $logger)
    {

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

    public function setNetworkRecovery($recover)
    {
        $this->network_recovery = $recover;
    }

    public function setTopologyRecovery($recover)
    {
        $this->topology_recovery = $recover;
    }

    public function setupTopology()
    {
        $chan = $this->getChannel();

        $chan->exchange_declare(
            $this->exchange->name,
            $this->exchange->type,
            false, // passive
            $this->exchange->durable,
            $this->exchange->auto_delete,
            $this->exchange->internal
        );

        $chan->queue_declare(
            $this->queue->name,
            false, // passive
            $this->queue->durable,
            $this->queue->exclusive,
            $this->queue->auto_delete
        );

        $chan->queue_bind(
            $this->queue->name,
            $this->exchange->name,
            $this->queue->routing_key
        );

    }

    public function getChannel()
    {
        if ($this->channel == null) {
            $amqp = $this->broker->getConnection();
            $this->channel = $amqp->channel();
        }
        return $this->channel;
    }

    public function reconnect()
    {
        try {
            $this->destroy();
        } catch (\Exception $e) {
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
            } catch (\Exception $e) {
                $this->logger->error($e);
                sleep(1);
            }
        }
    }

    public function consume(
        $no_ack, $exclusive, $callback, $consumer_tag = '')
    {
        $this->setupTopology();
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
            $this->queue->name,
            $this->consumer_tag,
            false, // no local
            $this->no_ack,
            $this->exclusive,
            false, // not wait
            $this->callback
        );
    }

    public function blockingConsume($interval = 1)
    {
        $chan = $this->getChannel();
        while (count($chan->callbacks)) {
            try {
                $chan->wait();
            } catch (\Exception $e) {
                $this->logger->warning($e);
                if ($this->network_recovery) {
                    $this->logger->warning("begin reconnect");
                    sleep($interval);
                    $this->reconnect();

                    if ($this->topology_recovery) {
                        $this->setupTopology();
                    }

                    $chan = $this->getChannel();
                    $this->_consume();
                    $this->logger->warning("reconnected");
                    continue;
                }
                throw $e;
            }
        }
    }

    public function destroy()
    {
        $chan = $this->getChannel();
        $this->channel = null;
        $broker = $this->broker;
        $this->broker = null;

        $chan->close();
        $broker->getConnection()->close();
    }
}
