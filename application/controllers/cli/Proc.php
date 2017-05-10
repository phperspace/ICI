<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'Base.php';

/**
 * 常驻进程类
 * 
 * @author Mr.Nobody
 *
 */
abstract class Proc extends Base
{

    /**
     * work
     * 实际进程执行的方法，此处为抽象方法，需要子类实现
     */
    abstract protected function _work();
    
    /**
     * 最大执行时间
     * 单位s
     * @var int
     */
    protected $_maxExcuteTime = -1;
    
    /**
     * 进程执行状态
     * @var int
     */
    const STATUS_PROC_START = 1;
    
    /**
     * 进程结束状态
     * @var int
     */
    const STATUS_PROC_STOP = 2;
    
    const DEFAULT_PROD_PID_FILE = 'proc.pid';

    /**
     * 重启
     */
    public function restart()
    {
        $this->stop();
        $this->start();
    }
    
    /**
     * 启动
     */
    public function start()
    {
    
        $this->_logMsg('proc start', 'NOTICE');

        // 检查进程是否已启动
        $pidPath = $this->_getPidDirPath();
        $pid = $this->_getPidCurrent($pidPath);
        if ('' != $pid) {
            $this->_logMsg('a proc has started before !', 'NOTICE');
            $this->_delPidFile($pid);
        }
        
        // 获取当前pid
        $pid = $this->_getPid();
        
        // 将pid写入文件
        $pidFile = $pidPath . $pid;
        file_put_contents($pidFile, self::STATUS_PROC_START);
        
        $startTime = microtime(TRUE);
    
        // 死循环
        while (TRUE) {

            // timeout 检查， -1 表示不限
            if (- 1 != $this->_maxExcuteTime) {
                $nowTime = microtime(TRUE);
                $passedMs = (int) ($nowTime - $startTime);
                if ($passedMs > $this->_maxExcuteTime) {
                    $this->_logMsg("time out and break. {$nowTime} - {$startTime} > {$this->_maxExcuteTime}", 'NOTICE');
                    break;
                }
            }

            // 检查接到stop命令
            $procStatus = $this->_getProcStatus($pid);
            if (self::STATUS_PROC_STOP == $procStatus) {
                $this->_logMsg("stop and break", 'NOTICE');
                break;
            }
            
            $this->_work();
    
        }
        
        // 退出前删除pid文件
        $this->_delPidFile($pid);
    
        // 主进程退出
        $this->_logMsg('proc finished and exit!!!', 'NOTICE');
        exit();
    }
    
    /**
     * 终止
     */
    public function stop($force = FALSE)
    {
        // 检查进程是否已启动
        $pidPath = $this->_getPidDirPath();
        $pid = $this->_getPidCurrent($pidPath);
        if (! $pid) {
            $this->_logMsg('stop failed no pid find', 'NOTICE');
            return;
        }

        if (! $force) {
            // 将pid写入文件
            $pidFile = $pidPath . $pid;
            file_put_contents($pidFile, self::STATUS_PROC_STOP);
            $this->_logMsg('a stop order send out!', 'NOTICE');
        } elseif (function_exists('posix_kill')) {
            $result = posix_kill($pid, 0);
            if ($result) {
                $this->_logMsg("{$pid} killed success.", 'NOTICE');
            } else {
                $this->_logMsg("kill {$pid} failed.", 'NOTICE');
            }
            $this->_delPidFile($pid);
        } else {
            $this->_logMsg('nothing to do!', 'NOTICE');
        }
    }

    /**
     * 日志
     *
     * @param string $msg
     * @param string $level
     */
    protected function _logMsg($msg, $level)
    {
        echo_debug_msg("[{$level}][{$msg}]" );
        
        $level = strtolower($level);
        if (! function_exists("log_{$level}")) {
            $level = 'notice';
        }
        
        $method = "log_{$level}";
        $method($msg);
    }
    
    /**
     * 取 pid 所在文件夹目录
     * @return string
     */
    protected function _getPidDirPath()
    {
        return APPPATH . "../var/pid/";
    }

    /**
     * 获取pid
     * 
     * @param string $pidPath
     * @return string
     */
    protected function _getPidCurrent($pidPath)
    {
        if (! function_exists('scandir')) {
            return self::DEFAULT_PROD_PID_FILE;
        }
        $cdir = scandir($pidPath);
        $pid = '';
        foreach ($cdir as $key => $value) {
            if (! in_array($value, array(".", "..", "index.html"))) {
                $pid = $value;
                break;
            }
        }
        return $pid;
    }
    
    /**
     * 删除pid文件
     * @param string $pid
     */
    protected function _delPidFile($pid)
    {
        $pidPath = $this->_getPidDirPath();
        $pidFilePath = $pidPath . $pid;
        if (! file_exists($pidFilePath)) {
            $this->_logMsg('delete pid file failed. file not exists', 'NOTICE');
            return;
        }
        unlink($pidFilePath);
        $this->_logMsg("delete pid file success.{$pid}", 'NOTICE');   
    }

    /**
     * 获得进程状态
     * @param string $pid
     */
    protected function _getProcStatus($pid)
    {
        $pidFile = $this->_getPidDirPath();
        $pidFile .= $pid;
        if (! file_exists($pidFile)) {
            return '';
        }
        return file_get_contents($pidFile);
    }

    /**
     * 取pid
     * 由于线上环境禁止posix相关函数，此处构造假的pid。
     * pid文件用来做信号量存储，做平滑退出用。
     */
    protected function _getPid()
    {
        if (function_exists('posix_getpid')) {
            return posix_getpid();
        }
        return self::DEFAULT_PROD_PID_FILE;
    }
    
}




