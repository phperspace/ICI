<?php

/**
 * redis代理类
 * 
 * @author Mr.Nobody
 */
class CRedis
{
    /**
     * 容器
     * 
     * @var array
     */
    protected static $_container;

    /**
     * 当前Redis连接Host
     *
     * @var array
     */
    private $_hostId = '';
    
    /**
     * 当前Redis连接实例
     * 
     * @var array
     */
    private $_redis = array();
    
    /**
     * 连接超时 s
     * 
     * @var float
     */
    private $_connectTimeout = 0.05;
    private $_readTimeout = 0.5;

    /**
     * 指令黑名单
     * 
     * @var array
     */
    private $_blackList =  array('KEYS', 'MOVE', 'OBJECT', 'RENAME', 'RENAMENX',
        'SORT', 'SCAN', 'BITOP', 'MSETNX', 'BLPOP', 'BRPOP',
        'BRPOPLPUSH', 'PSUBSCRIBE', 'PUBLISH', 'PUNSUBSCRIBE',
        'SUBSCRIBE', 'UNSUBSCRIBE', 'DISCARD', 'EXEC', 'MULTI',
        'UNWATCH', 'WATCH', 'SCRIPT EXISTS', 'SCRIPT FLUSH', 'SCRIPT KILL',
        'SCRIPT LOAD', 'AUTH', 'ECHO', 'SELECT', 'BGREWRITEAOF', 'BGSAVE',
        'CLIENT KILL', 'CLIENT LIST', 'CONFIG GET', 'CONFIG SET', 'CONFIG RESETSTAT',
        'DBSIZE', 'DEBUG OBJECT', 'DEBUG SEGFAULT', 'FLUSHALL', 'FLUSHDB', 'INFO', 'LASTSAVE',
        'MONITOR', 'SAVE', 'SHUTDOWN', 'SLAVEOF', 'SLOWLOG', 'SYNC', 'TIME');

    /**
     * 魔术方法
     * 获取CI对象的属性
     * @param string $key
     * @return mixed
     */
    function __get($key)
    {
        $CI = & get_instance();
        return $CI->$key;
    }

    public function __construct($hostId = array('default'))
    {
        $hostId = $hostId[0];
        self::$_container[$hostId] = $this->_connect($hostId);
        $this->_hostId = $hostId;
    }

    /**
     * 取指定的RedisProxy实例
     * 初始化指定id的myredis实例，并存在容器中。
     *
     * @param string $hostId
     * @return RedisProxy
     */
    public function switchHost($hostId)
    {
        if (! isset(self::$_container[$hostId])) {
            self::$_container[$hostId] = $this->_connect($hostId);
        }
        $this->_hostId = $hostId;
    }
    
    /**
     * 初始化和连接实例
     * 
     * @param array $config
     * @param array $options
     * @return Redis
     * @throws \Exception
     */
    protected function _connect($hostId)
    {
        // 配置检查
        $this->load->config('redis');
        $config = config_item('redis');
        $config = $config[$hostId];
        if (empty($config)) {
            throw new \Exception('redis server not configed ', 5002);
        }
        
        // 默认取0号实例
        $num = count($config);
        $no = 0;
        $redis = new \Redis();
        
        // pconnect
        if (! $redis->pconnect($config[$no]['host'], $config[$no]['port'], 
            ! empty($config[$no]['connectTime']) ? $config[$no]['connectTime'] : $this->_connectTimeout)) {
            // 如果第一个入口连接失败，选择下一个入口
            $no = ($no + 1) % $num;
            $redis = new \Redis();
            if (! $redis->pconnect($config[$no]['host'], $config[$no]['port'], 
                ! empty($config[$no]['connectTime']) ? $config[$no]['connectTime'] : $this->_connectTimeout)) {    
                throw new \Exception('connect redis server failed', 5002);
            }
        }
        
        // readTime
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, 
            ! empty($config[$no]['readTime']) ? $config[$no]['readTime'] : $this->_readTimeout);
        
        // auth
        if (isset($config[$no]['password']) && ! empty($config[$no]['password'])) {
            $result = $redis->auth($config[$no]['password']);
            if ($result == false) {
                throw new \Exception('redis auth failed', 5002);
            }
        }
        
        // select db
        if (isset($config[$no]['db']) && $config[$no]['db'] !== '') {
            $redis->select($config[$no]['db']);
        } else {
            $redis->select(0);
        }
        return $redis;

    }

    /**
     * \Redis代理请求
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $redis = self::$_container[$this->_hostId];
        
        // in blackList
        if (in_array(strtoupper($name), $this->_blackList)) {
            throw new \Exception($name . 'donot support!', 5002);
        }
        
        global $BM;
        $BM->mark('redis_start_time');
        
        try {
            if (strtolower($name) == 'zadd') {
                $arguments[0] = (string) $arguments[0];
            }
            $result = call_user_func_array(array(
                $redis,
                $name
            ), $arguments);
            
            // 返回false可能是连接已经断开，在这里检查下连接
            if ($result === false) {
                if (! $redis->ping()) {
                    // 尝试一次重连
                    $redis = self::$_container[$this->_hostId] = $this->_connect($this->_hostId);
                    if ($redis) {
                        $result = call_user_func_array(array(
                            $redis,
                            $name
                        ), $arguments);
                    }
                }
            }
            
            $BM->mark('redis_end_time');
        } catch (\Exception $e) {
            write_fatal(array(
                'errno' => $e->getCode(),
                'errmsg' => $e->getMessage(),
                'proc_time' => $BM->elapsed_time('redis_start_time', 'redis_end_time'),
                'method' => $name,
                'args' => json_encode($arguments)
            ), 'redis_failed');
            throw new \Exception("redis excute exception[code={$e->getCode()}][msg={$e->getMessage()}]", 5002);
        }
        
        write_notice(array(
            'errno' => 0,
            'errmsg' => 'succ',
            'proc_time' => $BM->elapsed_time('redis_start_time', 'redis_end_time'),
            'method' => $name,
            'args' => json_encode($arguments)
        ), 'redis_success');
        return $result;
    }

}
