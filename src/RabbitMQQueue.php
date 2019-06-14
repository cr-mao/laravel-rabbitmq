<?php
namespace Qianka\RabbitMQ;

class RabbitMQQueue
{

    public $name = null;
    public $durable = false;
    public $exclusive = false;
    public $auto_delete = false;
    public $routing_key = null;

    public function __construct($name, $durable, $exclusive, $auto_delete, $routing_key)
    {
        $this->name = $name;
        $this->durable = $durable;
        $this->exclusive = $exclusive;
        $this->auto_delete = $auto_delete;
        $this->routing_key = $routing_key;
    }

}
