## RabbitMQ Binding for Laravel  6


### install

add to `composer.json`

```
  "require": {
    ...
    "crmao/laravel-rabbitmq": "1.0.0",
    ...
  }
```

Add the Service Provider to `config/app.php`

```
PayCenter\RabbitMQ\RabbitMQServiceProvider::class,
```

Add the Facade to `config/app.php`

```
"RabbitMQ" => PayCenter\RabbitMQ\Facades\RabbitMQ::class,
```

add `config/rabbitmq.php`

```php
<?php
return [
    "connections" => [
        "default" => [
            "host" => '127.0.0.1',
            "port" => 5672,
            "username" => 'guest',
            "password" => 'guest',
            "vhost" => '/',
            "heartbeat_interval" => 120,
        ]
    ]
];
```


### usage

Publisher

```php

```

Consumer

```php

```
