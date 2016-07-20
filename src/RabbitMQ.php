<?php namespace Qianka\RabbitMQ;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class RabbitMQ {

    protected $config = null;
    protected $instances = null;

    protected $logger = null;

    public function __construct ($config)
    {
        $this->config = $config;
        $this->instances = [];
        $this->configLogger();
    }

    public function configLogger(
        $path = "php://stdout", $level = Logger::WARNING)
    {
        $log = new Logger("laravel-rabbitmq");
        $handler = new StreamHandler($path, $level);
        $log->pushHandler($handler);
        $this->logger = $log;
    }

    public function getConfig()
    {
        return $this->config;
    }

    protected function getConnectionConfig($name)
    {
        if (!array_key_exists($name, $this->config['connections'])) {
            throw new RabbitMQException(
                "cannot find \"{$name}\" in rabbitmq config");
        }
        return $this->config['connections'][$name];
    }

    public function getConnection(
        $name = "default",
        $use_heartbeat = false)
    {
        $inst = null;
        $config = $this->getConnectionConfig($name);

        if (array_key_exists($name, $this->instances)) {
            $inst = $this->instances[$name];
            return $inst->getConnection();
        }

        $host = $config['host'];
        $port = $config['port'];
        $username = $config['username'];
        $password = $config['password'];
        $vhost = $config['vhost'];
        $heartbeat_interval = 0;

        if ($use_heartbeat)
            $heartbeat_interval = $config['heartbeat_interval'];

        $inst = new RabbitMQBroker(
            $host,
            $port,
            $username,
            $password,
            $vhost,
            $heartbeat_interval
        );

        $this->instances[$name] = $inst;
        return $inst->getConnection();
    }

    public function destroyConnection($name = "default") {
        unset($this->instances[$name]);
    }

    public function createPublisher(
        $exchange, $name = "default", $use_heartbeat = false)
    {
        $config = $this->getConnectionConfig($name);
        $host = $config['host'];
        $port = $config['port'];
        $username = $config['username'];
        $password = $config['password'];
        $vhost = $config['vhost'];
        $heartbeat_interval = 0;

        if ($use_heartbeat)
            $heartbeat_interval = $config['heartbeat_interval'];

        $rv = new RabbitMQPublisher(
            $host,
            $port,
            $username,
            $password,
            $vhost,
            $heartbeat_interval,
            $exchange,
            $this->logger
        );
        return $rv;
    }

    public function createConsumer(
        $exchange, $queue, $name = "default", $use_heartbeat = false)
    {
        $config = $this->getConnectionConfig($name);
        $host = $config['host'];
        $port = $config['port'];
        $username = $config['username'];
        $password = $config['password'];
        $vhost = $config['vhost'];
        $heartbeat_interval = 0;

        if ($use_heartbeat)
            $heartbeat_interval = $config['heartbeat_interval'];

        $rv = new RabbitMQConsumer(
            $host,
            $port,
            $username,
            $password,
            $vhost,
            $heartbeat_interval,
            $exchange,
            $queue,
            $this->logger
        );
        return $rv;
    }
}
