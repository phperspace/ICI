<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'Proc.php';

/**
 * 常驻进程类
 * 
 * @author Mr.Nobody
 *
 */
class Hello extends Proc
{

    /**
     * 最大执行时间10个小时
     * 
     * @var int
     */
    protected $_maxExcuteTime = 36000;
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * work
     * 实现父类方法
     */
    protected function _work()
    {
        echo "hello world!";
        sleep(5);
    }
    
}




