<?php
namespace PayCenter\RabbitMQ\Facades;

use Illuminate\Support\Facades\Facade;

class RabbitMQ extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'PayCenter\RabbitMQ\RabbitMQ';
    }

}
