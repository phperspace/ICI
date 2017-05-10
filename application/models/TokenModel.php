<?php

class TokenModel extends CI_Model 
{
    
    /**
     * token 过期时间
     * 30 天
     * @var int
     */
    private static $_TOKEN_TIMEOUT = 2592000;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->load->library("RedisPool", "", "redisPool");
    }
    
    /**
     * 生成一个token
     */
    public function getAToken()
    {
        // 取毫秒时间，13位
        $time = microtime();
        $timeval = substr($time, 11) . substr($time, 2, 3);
        
        
        $suffix = '';
        for ($i = 0; $i < rand(5, 10); ++ $i) {
            // 62随机取30位的排列
            $suffix .= substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, rand(20, 30));
        }
        
        return $timeval . $suffix;
    }
    
    /**
     * 存用户的token值
     * 
     * @param string $token
     * @param array $params
     * @return boolean
     */
    public function saveTokenDatas($token, $params)
    {

        $keys = array('id', 'email', 'mobile', 'mac', 'name', 'username', 'password', 'salt', 'random_code', 'register_type');
        $data = get_args_by_keys($params, $keys);
        
        $tokenKey = $this->_getKeyOfToken($token);
        $redis = $this->redisPool->getConnInstance();
        
        // 存储token数据
        $redis->hmset($tokenKey, $data);
        $redis->expire($tokenKey, self::$_TOKEN_TIMEOUT);
        
    }
    
    public function getTokenDatas($token)
    {
        $tokenKey = $this->_getKeyOfToken($token);
        $redis = $this->redisPool->getConnInstance();
        $datas = $redis->hgetall($tokenKey);
        // 每次活跃后token过期时间重置
        if (! empty($datas)) {
            $redis->expire($tokenKey, self::$_TOKEN_TIMEOUT);
        }
        return $datas;
    }
    
    public function deleteToken($token)
    {
        $tokenKey = $this->_getKeyOfToken($token);
        $redis = $this->redisPool->getConnInstance();
        return $redis->del($tokenKey);
    }
    
    private function _getKeyOfToken($token)
    {
        return 'token:' . md5($token);        
    }
    
    /**
     * 注册验证随机码
     */
    public function getARandomCode()
    {
        return mt_rand(1000, 9999);        
    }
    
    public function checkRandomCode($token, $randomCode)
    {
        $datas = $this->getTokenDatas($token);
        return $datas['random_code'] == $randomCode;
    }

   
}
