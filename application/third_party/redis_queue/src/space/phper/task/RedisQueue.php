<?php 
namespace Src\Space\Phper\Task;

/**
 * reidis 消息队列
 * 
 * @author phper.space
 */
class RedisQueue extends Queue implements QueueInterface 
{

    /**
     * 单例
     *
     * @var RedisQueue
     */
    private static $_instance;

    /**
     * redis实例
     * 
     * @var RedisClient
     */
    protected $redisClient;

    /**
     * 队列名
     *
     * @var string
     */
    protected $queueName;

    /**
     * 资源锁的key
     *
     * @var string
     */
    protected static $_KEY_OF_SOURCE_LOCK = 'redis_queue_source_lock_key';
    
    /**
     * 私有化构造方法
     */
    private function __construct()
    {
    }

    /**
     * 初始化
     *
     * @param  RedisClient $redisClient
     * @param  string      $default
     */
    protected function init($redisClient, $queueName)
    {
        $this->redisClient = $redisClient;
        $this->queueName   = $queueName;
    }
    
    /**
     * 取实例
     *
     * @param  RedisClient $redisClient
     * @param  string      $default
     * @return RedisQueue
     */
    public static function getInstance($redisClient, $queueName)
    {
        if (NULL == self::$_instance) {
            self::$_instance = new self();
        }
        self::$_instance->init($redisClient, $queueName);
        return self::$_instance;
    }

    /**
     * 推入一个job
     * 
     * @param  string  $job    eg. $job = 'class@method'
     * @param  mixed   $data
     * @param  string  $queue
     * @return void
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * 推入一个payload
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = array())
    {
        $this->redisClient->rpush($this->getQueue($queue), $payload);
        $payload = json_decode($payload, true);

        return $payload['id'];
    }

    /**
     * 推入一个延时的job
     *
     * @param  \DateTime|int(s)    $delay
     * @param  string              $job
     * @param  mixed               $data
     * @param  string              $queue
     * @return void
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = $this->createPayload($job, $data);

        $delay = $this->getSeconds($delay);

        $this->redisClient->zadd($this->getQueue($queue).':delayed', $this->getTime() + $delay, $payload);

        $payload = json_decode($payload, true);

        return $payload['id'];
    }

    /**
     * 释放job回到队列中
     * 加入延时队列
     *
     * @param  string  $queue
     * @param  string  $payload
     * @param  int     $delay
     * @param  int     $attempts
     * @return void
     */
    public function release($queue, $payload, $delay, $attempts)
    {
        $payload = $this->setMeta($payload, 'attempts', $attempts);

        $this->redisClient->zadd($this->getQueue($queue).':delayed', $this->getTime() + $delay, $payload);
    }

    /**
     * 取出一个job
     *
     * @param  string  $queue
     * @return Job|null
     */
    public function pop($queue = null)
    {
        $original = $queue ?: $this->queueName;

        $this->migrateAllExpiredJobs($queue = $this->getQueue($queue));

        $job = $this->redisClient->lpop($queue);

        if ( ! is_null($job) && $job !== false)
        {
            $this->redisClient->zadd($queue.':reserved', $this->getTime() + 60, $job);

            return new RedisJob($this, $job, $original);
        }
    }

    /**
     * 删除保留任务
     *
     * @param  string  $queue
     * @param  string  $job
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        $this->redisClient->zrem($this->getQueue($queue).':reserved', $job);
    }

    /**
     * 移动到期的任务
     * 包括延时任务、保留任务
     * 
     * @param  string  $queue
     * @return void
     */
    protected function migrateAllExpiredJobs($queue)
    {
        $this->migrateExpiredJobs($queue.':delayed', $queue);

        $this->migrateExpiredJobs($queue.':reserved', $queue);
    }

    /**
     * 移动指定队列的到期任务
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function migrateExpiredJobs($from, $to)
    {
       // 取到期任务 
       $jobs = $this->getExpiredJobs($from, $time = $this->getTime());
       if (count($jobs) <= 0) {
           return;
       }
       
        // 锁的key
        $lockKey = self::$_KEY_OF_SOURCE_LOCK . $from;
        
        // 上锁
        $lock = $this->redisClient->setnx($lockKey, 1);
        
        // 锁的过期时间设1s
        $this->redisClient->expire($lockKey, 1);

        // 上锁失败，则不继续执行
        if (! $lock) {
            return;
        }
        
        // 上锁成功，则执行
        $this->removeExpiredJobs($from, $time);
        call_user_func_array(array($this->redisClient, 'rpush'), array_merge(array($to), $jobs));
        
        // 解锁
        $this->redisClient->del($lockKey);
    }

    /**
     * 取到期的任务
     *
     * @param  string  $queue
     * @param  int     $time
     * @return array
     */
    protected function getExpiredJobs($queue, $time)
    {
        return $this->redisClient->zrangebyscore($queue, '-inf', $time);
    }

    /**
     * 删除过期任务
     *
     * @param  string  $queue
     * @param  int     $time
     * @return void
     */
    protected function removeExpiredJobs($queue, $time)
    {
        $this->redisClient->zremrangebyscore($queue, '-inf', $time);
    }

    /**
     * 构造payload
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        $payload = parent::createPayload($job, $data);

        $payload = $this->setMeta($payload, 'id', $this->getRandomId());

        return $this->setMeta($payload, 'attempts', 1);
    }

    /**
     * 构造一个随机id
     *
     * @return string
     */
    protected function getRandomId($length = 32)
    {
        
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    /**
     * 取队列的key
     * 可配置
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        global $phper_space_task_queue_config;
        $prefix = empty($phper_space_task_queue_config['redis']['prefix']) ? 'task_queue_' : $phper_space_task_queue_config['redis']['prefix'];
        $queueKey = $prefix . ($queue ? $queue : $this->queueName);
        return $queueKey;
    }

    /**
     * 返回redis实例
     *
     * @return RedisClient
     */
    public function getRedis()
    {
        return $this->redisClient;
    }

}
