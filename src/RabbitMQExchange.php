<?php
namespace Qianka\RabbitMQ;

class RabbitMQExchange
{

    public $name = null;
    public $type = null;
    public $durable = false;
    public $auto_delete = false;
    public $internal = false;

    public function __construct($name, $type, $durable, $auto_delete, $internal = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->durable = $durable;
        $this->auto_delete = $auto_delete;
        $this->internal = $internal;
    }


}
