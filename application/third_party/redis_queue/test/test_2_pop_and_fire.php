<?php

require_once __DIR__ . '/../autoload.php';

use Src\Space\Phper\Task\RedisQueue;

$redisClient = new Redis();
$redisClient->connect('127.0.0.1', 6379);

$queueName = 'test';
$redisQueue = RedisQueue::getInstance($redisClient, $queueName);

while (TRUE) {

    $task = $redisQueue->pop();
    
    if (! $task) {
        echo "nothing to do", "\r\n";
        sleep(1);
        continue;
    }

    $rowBody = $task->getRawBody();
    
    var_dump($rowBody);

    $task->fire();
    
}




