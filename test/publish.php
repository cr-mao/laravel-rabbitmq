<?php
/**
 * Desc:
 * User: maozhongyu
 * Date: 2022/1/20
 * Time: 下午3:01
 */
require_once __DIR__ . "/../vendor/autoload.php";

use PayCenter\RabbitMQ\RabbitMQ;


sendMQ(array(
    "send_mq"=>1
),
    "tmp_mao_test_exchange",
            "tmp_mao_test"
);


function sendMQ($pubData, $exchange, $routingKey)
{
    $config = array(
        "connections"=>array(
            "default"=>array(
                'host' => '47.103.78.179',
                'port' => '5672',
                'username' => 'admin',
                'password' => '123456',
                'vhost' => '/',
                "heartbeat_interval" => 30,
            )
        )
    );
    $mq = new RabbitMQ($config);
    $pub = $mq->createPublisher("default");
    $pub->sendMessage($pubData, $exchange, $routingKey);
    $pub->destroy();
}