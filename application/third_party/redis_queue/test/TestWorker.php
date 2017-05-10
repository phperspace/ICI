<?php
namespace test;

use Src\Space\Phper\Task\RedisJob;

/**
 * 一个worker类例子
 * 
 * @author phper.space
 *
 */
class TestWorker
{

    /**
     * fire 方法
     * 调用$job->fire()，最终会执行到该方法
     * 
     * @param   RedisJob $job
     * @param   Array    $data
     * @return  bool
     */
    public function fire(RedisJob $job, Array $data)
    {
        echo "fire", "\r\n";
        echo "attempts", $job->attempts(), "\r\n";
        
        // 执行成功，注意要调用delete
        if (! $this->isFireSuccess()) {
            echo "execute success", "\r\n";
            $job->delete();
            return TRUE;
        }

        // 执行失败，设定重试时间
        echo "execute failed and release", "\r\n";
        $retryTime = self::Fibonacci($job->attempts());
        $job->release($retryTime);
        
    }

    /**
     * 是否执行成功
     * 一个测试方法，随机返回true/false
     * 
     * @return bool
     */
    private function isFireSuccess()
    {
        if (time() % 2) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 
     * @param int $n
     */
    private static function Fibonacci($n)
    {
        if ($n <= 2) {
            return 1;
        }
        return self::Fibonacci($n - 1) + self::Fibonacci($n - 2);
    }
}

?>