# 基于redis的任务队列服务   

## 特点        
1.动态：可指定任务处理的类和方法    
2.备份：出队备份，防止任务执行过程中意外退出造成数据丢失      
3.重试：支持失败重试策略（延时、次数）    
4.轻量级：除redis、php，无需额外启动任何服务    

## 原理   
1.借助redis数据结构：用一个list做队列结构，一个zset做备份，一个zset做延时重试    
2.用setnx模拟读锁，保证并发安全    
3.采用“容器模式”，实现各worker对象的单例化    
    
# 使用       

## 开始       

1.必须要先引入autoload.php，如下：  

    require_once '../autoload.php';  
    
2.引入相关的类及命名空间，如下：
  
    // 引入RedisQueue    
    use Src\Space\Phper\Task\RedisQueue;  

## RedisQueue相关 ——参照RedisQueue类                 

1.push ——推入队列        

    // 此处指定该任务由Test\TestWorker类的fire方法执行
    $job = 'Test\TestWorker@fire';
    $data = array('hello' => 'push');
    $redisQueue->push($job, $data);
    
2.pop ——从队列取出   

    // 取一个任务    
    $task = $redisQueue->pop();
    // 执行一个任务    
    $task->fire();    
    
3.later ——推入延时队列           

    $job = 'Test\TestWorker@fire';
    $data = array('hello' => 'push');
    // 延时10s后执行
    $redisQueue->later(10, $job, $data);   

4.regist ——外部注入worker（可选）   

    // 一般来说，不需要外部注入worker，程序会通过new的方式自动创建worker；
    // 但是，对于CodeIgniter等不支持命名空间的框架，必须先自行往WorkerContainer注入Worker对象
    // 如下，取worker容器类，通过外部注入worker
    $container = WorkerContainer::getInstance();
    $task = $redisQueue->pop();    
    $class = $task->getName();    
    if (! $container->fetch($class)) {    
        $container->regist($class, new $class());    
    }    
    // fire    
    $task->fire();    

## RedisJob相关 ——参照RedisJob类        

1.fire ——执行一个任务   

    $task->fire();    
    
2.release ——延时重试   

    // 释放job回到队列中，指定时间30s后重试    
    $task->release(30);    
    
3.delete ——删除任务    

    // 执行任务成功，需要调用delete做删除    
    $task->delete();    
      
4.attempts ——取尝试次数   

    $attempts = $task->attempts();    
    if ($attempts == 1)  {    
        // 第一次执行    
    } elseif($attempts <= 5) {    
        // 尝试5次以内    
    } else {    
        // 尝试5次以上    
    }   
    
# 例子  
         
    参照 test 目录
    
    
    
    
    
