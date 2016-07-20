## RabbitMQ Binding for Laravel 5


### install

add to `composer.json`

```
  "repositories": [
    {
      "type": "git",
      "url": "ssh://git@git.corp.qianka.com/chenlei/laravel-rabbitmq"
    }
  ]
  ...

  "require": {
    ...
    "qianka/laravel-rabbitmq": "dev-master",
    ...
  }
```

Add the Service Provider to `config/app.php`

```
Qianka\RabbitMQ\RabbitMQServiceProvider::class,
```

Add the Facade to `config/app.php`

```
"RabbitMQ" => Qianka\RabbitMQ\Facades\RabbitMQ::class,
```

add `config/rabbitmq.php`

```php
<?php

return [
  "default" => [
    "host" => "127.0.0.1",
    "port" => 5672,
    "username" => "guest",
    "password" => "guest",
    "vhost" => "/",
    "heartbeat_interval" => 30,
  ],
  "somethingElse" => [
    ...
  ],
];
```


### usage

As a Facade

```php
// will return an AMQPConnection object from php-amqplib package
$amqp_connection = RabbitMQ::getConnection();

// use another connection
$amqp_connection = RabbitMQ::getConnection("somethingElse");


// enable heartbeat which is disabled by default
// (would be useful as a consumer)
$amqp_connection = RabbitMQ::getConnection("someName", true);
```

Publisher

```php

$payload = ["hello" => date('Y-m-d H:i:s')];
$exchange = "test.laravel.topic";

$pub = RabbitMQ::createPublisher($exchange, "cluster");

$pub->declareExchange(
    "topic", // type
    true,    // durable
    false,   // auto delete
    false    // internal
);

$pub->sendMessage($payload); // unserialized message
```

Consumer

```php
function process_message($message) {
    var_dump($message->body);

    $message->delivery_info['channel']->
        basic_ack($message->delivery_info['delivery_tag']);
}

$exchange = "test.laravel.topic";
$queue = "test.laravel";
$consumer = RabbitMQ::createConsumer(
    $exchange,
    $queue,
    "cluster",       // name
    false);           // use_heartbeat

$consumer->declareQueue(
    false, // durable
    false, // exclusive
    false  // auto_delete
);
$consumer->bindQueue($exchange, "#");

$consumer->consume(
    false,  // no ack
    false, // exclusive
    function ($message) {
        $this->process_message($message);
    }
);

$consumer->blockingConsume();
```
