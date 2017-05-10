<?php

require_once __DIR__ . '/../autoload.php';

use Src\Space\Phper\Task\RedisQueue;

$redisClient = new Redis();
$redisClient->connect('127.0.0.1', 6379);

$queueName = 'test';

$redisQueue = RedisQueue::getInstance($redisClient, $queueName);

$job = 'Test\TestWorker@fire';
$data = array('hello' => 'push');

$redisQueue->push($job, $data);

$data = array('hello' => 'later');
$redisQueue->later(100, $job, $data);


