<?php namespace Qianka\RabbitMQ\Facades;

use Illuminate\Support\Facades\Facade;

class RabbitMQ extends Facade {

    protected static function getFacadeAccessor()
    {
        return 'Qianka\RabbitMQ\RabbitMQ';
    }

}
