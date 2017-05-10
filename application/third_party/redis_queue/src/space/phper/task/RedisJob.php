<?php 
namespace Src\Space\Phper\Task;

/**
 * redis任务类
 *
 * @author phper.space
 */
class RedisJob extends Job 
{

    /**
     * RedisQueue实例
     *
     * @var RedisQueue
     */
    protected $redisQueue;

    /**
     * 当前job的payload
     *
     * @var string
     */
    protected $payload;
    
    /**
     * 所在的队列名称
     *
     * @var string
     */
    protected $queueName;

    /**
     * 构造方法
     * 
     * @param  RedisQueue  $redisQueue
     * @param  string      $job
     * @param  string      $queue
     */
    public function __construct(RedisQueue $redisQueue, $job, $queue)
    {
        $this->redisQueue = $redisQueue;
        $this->payload    = $job;
        $this->queueName  = $queue;
    }

    /**
     * 执行当前任务
     * @param  obj   $worker
     * @return void
     */
    public function fire()
    {
        $this->resolveAndFire(json_decode($this->getRawBody(), TRUE));
    }

    /**
     * 取当前job的payload
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->payload;
    }

    /**
     * 删除当前任务
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->redisQueue->deleteReserved($this->queueName, $this->payload);
    }

    /**
     * 释放job回到队列中
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->delete();

        $this->redisQueue->release($this->queueName, $this->payload, $delay, $this->attempts() + 1);
    }

    /**
     * 取尝试次数
     *
     * @return int
     */
    public function attempts()
    {
        $job = json_decode($this->payload, true);
        return $job['attempts'];
    }

    /**
     * 取任务id
     *
     * @return string
     */
    public function getJobId()
    {
        $job = json_decode($this->payload, true);
        return $job['id'];
    }

    /**
     * 取RedisQueue实例
     *
     * @return RedisQueue
     */
    public function getRedisQueue()
    {
        return $this->redisQueue;
    }

    /**
     * 取当前job的payload
     *
     * @return string
     */
    public function getRedisJob()
    {
        return $this->payload;
    }

}