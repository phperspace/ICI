<?php
namespace Src\Space\Phper\Task;

/**
 * 队列接口
 * 
 * @author phper.space
 */
interface QueueInterface
{

    /**
     * 推入一个job
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null);

    /**
     * 推入一个payload
     * payload理解为任务的数据包
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = array());

    /**
     * 推入一个延时的job
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null);

    /**
     * 取出一个job
     *
     * @param  string  $queue
     * @return Job|null
     */
    public function pop($queue = null);

}
