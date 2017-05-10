<?php 
namespace Src\Space\Phper\Task;

/**
 * 队列抽象类
 * 
 * @author phper.space
 */
abstract class Queue
{

    /**
     * 推入一批job
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }
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
        return json_encode(array(
            'job' => $job,
            'data' => $data
        ));
    }

    /**
     * 向payload中添加一对k-v
     *
     * @param  string  $payload
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    protected function setMeta($payload, $key, $value)
    {
        $payload = json_decode($payload, true);
        $payload[$key] = $value;
        return json_encode($payload);
    }

    /**
     * 计算超时秒数
     *
     * @param  \DateTime|int  $delay
     * @return int
     */
    protected function getSeconds($delay)
    {
        if ($delay instanceof \DateTime) {
            return max(0, $delay->getTimestamp() - $this->getTime());
        } else {
            return intval($delay);
        }
    }

    /**
     * 取当前时间
     * unix时间戳
     * 
     * @return int
     */
    public function getTime()
    {
        return time();
    }



}
