<?php

require_once __DIR__ . '/../autoload.php';

use Src\Space\Phper\Task\RedisQueue;
use Src\Space\Phper\Task\WorkerContainer;

$redisClient = new Redis();
$redisClient->connect('127.0.0.1', 6379);

$queueName = 'test';
$redisQueue = RedisQueue::getInstance($redisClient, $queueName);

$container = WorkerContainer::getInstance();

while (TRUE) {

    $task = $redisQueue->pop();
    
    if (! $task) {
        echo "nothing to do", "\r\n";
        sleep(1);
        continue;
    }

    $rowBody = $task->getRawBody();
    
    echo $rowBody, "\r\n";

    $class = $task->getName();
    if (! $container->fetch($class)) {
        $container->regist($class, new $class());
    }
    
    
    $task->fire();
    
}




