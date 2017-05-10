<?php
namespace Src\Space\Phper\Task;

class WorkerContainer
{

    /**
     * 单例
     *
     * @var WorkerRegister
     */
    private static $_instance;

    /**
     * 容器
     *
     * @var array
     */
    protected static $container;

    /**
     * 私有化构造方法
     */
    private function __construct()
    {}

    /**
     * 取实例
     *
     * @return WorkerRegister
     */
    public static function getInstance()
    {
        if (NULL == self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 注册一个worker实例
     *
     * @param string $alias            
     * @param string $object            
     */
    public static function regist($alias, $object)
    {
        self::$container[$alias] = $object;
    }

    /**
     * 取一个worker实例
     *
     * @param string $alias            
     * @return Worker
     */
    public static function fetch($key)
    {
        if (! isset(self::$container[$key])) {
            return false;
        }
        return self::$container[$key];
    }

    /**
     * 删除一个worker实例
     *
     * @param string $alias            
     */
    public function remove($alias)
    {
        unset(self::$container[$alias]);
    }
}
