<?php 
namespace Src\Space\Phper\Task;


/**
 * 抽象任务类
 * 
 * @author phper.space
 */
abstract class Job {

    /**
     * 实例
     *
     * @var mixed
     */
    protected $instance;

    /**
     * 所属队列名称
     *
     * @var string
     */
    protected $queue;

    /**
     * 删除标识
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * 执行
     *
     * @return void
     */
    abstract public function fire();

    /**
     * 删除
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * 是否被删除
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * 释放工作回到队列中
     *
     * @param  int   $delay
     * @return void
     */
    abstract public function release($delay = 0);

    /**
     * 取重试次数
     *
     * @return int
     */
    abstract public function attempts();

    /**
     * 取任务体
     * 
     * @return string
     */
    abstract public function getRawBody();

    /**
     * 获取job执行对象并执行
     * 
     * @param  array  $payload
     * @return void
     */
    protected function resolveAndFire(array $payload)
    {
        list($class, $method) = $this->parseJob($payload['job']);

        $worker = $this->resolve($class);

        $worker->{$method}($this, $payload['data']);
    }

    /**
     * 获取job执行对象（Worker对象）
     * 首先会尝试去WorkerContainer里取，取不到会尝试new，并放入WorkerContainer
     * ！！！注意，这里new的方式，仅限于对命名空间的类解析并生成对象。
     * 对于CodeIgniter等不支持命名空间的框架，必须先自行往WorkerContainer注入Worker对象
     * @param  string  $class
     * @return mixed
     */
    protected function resolve($class)
    {
        $workerContainer = WorkerContainer::getInstance();
        $worker = WorkerContainer::fetch($class);
        if (FALSE === $worker) {
            $worker = new $class();
            WorkerContainer::regist($class, $worker);
        }
        
        return $worker;
    }

    /**
     * 解析执行job的类和方法
     * 
     * @param  string  $job
     * @return array
     */
    protected function parseJob($job)
    {
        $segments = explode('@', $job);

        return count($segments) > 1 ? $segments : array($segments[0], 'fire');
    }

    /**
     * 取job原始类名
     * 即实际执行job的类名
     *
     * @return string
     */
    public function getName()
    {
        $body = json_decode($this->getRawBody(), true);
        list($class, $method) = $this->parseJob($body['job']);
        return $class;
    }

    /**
     * 是否自动删除
     *
     * @return bool
     */
    public function autoDelete()
    {
        return isset($this->instance->delete);
    }

    /**
     * 取所在队列名称
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

}
