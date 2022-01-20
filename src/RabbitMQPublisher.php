<?php
namespace PayCenter\RabbitMQ;

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

    /**
     * 生成 topic MQ
     * @param $body
     * @param $exchange
     * @param $routingKey
     * @param bool $encode
     */
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
     * 生成delay MQ  基于死信队列
     * @param $body
     * @param $exchange
     * @param $routingKey
     * @param $delayTime
     * @param bool $encode
     */
    public function sendDelayMessage($body, $exchange,$dead_exchange,$delay_queue, $dead_queue,$routingKey, $delayTime, $encode = true)
    {
        $payload = $body;
        if ($encode) $payload = json_encode($body);
        $channel = $this->getChannel();
        //声明一个普通交换机
        $channel->exchange_declare(
            $exchange,
            'topic',
            false,
            true,
            false,
            false,
            false
        );
        //声明一个死信交换机
        $channel->exchange_declare($dead_exchange,'topic',false,true,false,false,false);

        $dead_routing_key = $dead_exchange;
        //声明一个延迟队列
        $channel->queue_declare($delay_queue,false,true,false,false,false,new AMQPTable([
            'x-dead-letter-exchange' => $dead_exchange,
            'x-dead-letter-routing-key' => $dead_routing_key,
            'x-message-ttl' => $delayTime * 1000
        ]));

        //绑定延迟队列到正常交换机上
        $channel->queue_bind($delay_queue,$exchange,$routingKey);

        //声明一个死信队列
        $channel->queue_declare($dead_queue,false,true,false,false,false);

        //绑定死信队列到延迟交换机上
        $channel->queue_bind($dead_queue,$dead_exchange,$dead_routing_key);


        $message = new AMQPMessage($payload,['delivery_mode'=>AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $channel->basic_publish($message,$exchange,$routingKey);
    }


    /**
     * 生成delay MQ  基于延迟插件实现方式
     * @param $body
     * @param $exchange
     * @param $routingKey
     * @param $delayTime
     * @param bool $encode
     */
    public function sendDelayMessageByDelayTool($body, $exchange, $routingKey, $delayTime, $encode = true, $queue = '')
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

        if (!empty($queue)) {
            $channel->queue_declare(
                $queue,
                false,
                true,
                false,
                false,
                false,
                new AMQPTable(
                    ["x-delayed-type" => "topic"]
                )
            );
            $channel->queue_bind($queue, $exchange, $routingKey);
        }

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