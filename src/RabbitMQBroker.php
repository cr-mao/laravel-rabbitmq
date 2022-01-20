<?php
namespace PayCenter\RabbitMQ;

use PhpAmqpLib\Connection\AMQPConnection;

class RabbitMQBroker
{

    protected $connection = null;

    public function __construct(
        $host,
        $port,
        $username = 'guest',
        $password = 'guest',
        $vhost = '/',
        $heartbeat_interval = 0)
    {

        $read_write_timeout = 3.0;
        if ($heartbeat_interval)
            $read_write_timeout = $heartbeat_interval * 2;

        $connection = new AMQPConnection(
            $host,
            $port,
            $username,
            $password,
            $vhost,
            false,      // $insist
            'AMQPLAIN', // $login_method =
            null,       // $login_response = null,
            'en_US',    // $locale,
            3,          // $connection_timeout
            $read_write_timeout,         // $read_write_timeout
            null,       // $context
            false,      // $keepalive
            $heartbeat_interval // heartbeat
        );

        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
