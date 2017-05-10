<?php
namespace Library;

/**
 * Api请求代理
 *
 * @author Mr.Nobody
 *        
 */
abstract class ApiProxy
{

    /**
     * GET 请求
     * 
     * @var string
     */
    const HTTP_REQUEST_METHOD_GET = 'GET';
    
    /**
     * POST 请求
     * 
     * @var string
     */
    const HTTP_REQUEST_METHOD_POST = 'POST';
    
    /**
     * CI 实例
     * 
     * @var CI
     */
    protected $_ci;
    
    /**
     * Curl 实例
     * 
     * @var Curl
     */
    protected $_curl;

    /**
     * api配置
     *
     * @var array
     */
    protected $_config;

    /**
     * 当前api标识
     *
     * @var string
     */
    protected static $_apiFlag;
    
    protected $_retStatus = 'errcode';
    protected $_retMsg = 'errmsg';

    /**
     * 构造方法
     */
    public function __construct($_apiFlag)
    {
        $this->_init($_apiFlag);
    }
    
    /**
     * http请求
     * 
     * @param string $api
     * @param array $params
     * @param string $method
     * @param func $checkResultFunc
     * @param number $connectTimeout 毫秒数
     * @param number $excuteTimeout 毫秒数
     * @throws \Exception
     * @return array
     */
    public function request($api, $params = array(), $method = self::HTTP_REQUEST_METHOD_GET, $checkResultFunc = '', $connectTimeout = 200, $excuteTimeout = 1000)
    {
        
        // 取请求地址
        $url = $this->_config['host'] . $this->_config['uri'][$api];
        
        // 通用参数
        $this->_addCommonParams($params);
        
        // 签名
        $this->_sign($params);
        
        // 获取请求结果
        $result = $this->_processRequest($url, $params, $method, $connectTimeout, $excuteTimeout);

        // 验证请求结果
        if ($checkResultFunc && ! $checkResultFunc($result)) {
            self::logRequestException($url, $method, $params, $result);
            throw new \Exception("请求结果不符合预期格式", 50102);
        }
        
        // 记录日志
        if (in_array($api, $this->_config['request_log'])) {
            self::logRequestResult($url, $method, $params, $result, 'notice');
        }
        
        return $result;        
    }
    
    /**
     * 记录请求失败日志
     * 
     * @param string $url
     * @param string $httpMethod
     * @param array $params
     * @param array $result
     */
    public static function logRequestException($url, $httpMethod, $params, $result, $level = 'notice')
    {
        $loginfo = array(
            "errmsg" => self::$_apiFlag . "_request_exception",
            "url" => $url,
            "params" => $params,
            "result" => $result
        );
        
        $logFunc = "write_" . strtolower($level);
        if (! function_exists($logFunc)) {
            $logFunc = 'write_notice';
        } 

        $logFunc(self::$_apiFlag . "_request", $loginfo);
    }
    

    /**
     * 记录正常请求日志
     *
     * @param string $url
     * @param string $httpMethod
     * @param array $params
     * @param array $result
     */
    public static function logRequestResult($url, $httpMethod, $params, $result, $level = 'notice')
    {
        $loginfo = array(
            "errmsg" => self::$_apiFlag . "_request_result",
            "url" => $url,
            "method" => $httpMethod,
            "params" => $params,
            "result" => $result
        );
        
        $logFunc = "write_" . strtolower($level);
        if (! function_exists($logFunc)) {
            $logFunc = 'write_notice';
        } 

        $logFunc(self::$_apiFlag . "_request", $loginfo);
    }
    
    /**
     * 添加通用参数
     * 子类需覆盖
     * @param array &$params
     */
    protected function _addCommonParams(&$params) {}
    
    /**
     * 签名方法
     * 子类需覆盖
     * @param array &$params
    */
    abstract protected function _sign(&$params) {}

    /**
     * 初始化
     *
     * @param string $_apiFlag
     */
    protected function _init($_apiFlag)
    {
        $this->_ci = &get_instance();
        $this->_curl = $this->_ci->load->library('Curl');
        
        self::$_apiFlag = $_apiFlag;
        $this->_ci->config->load('api');
        $this->_config = $this->_ci->Config->item(self::$_apiFlag);

    }
    
    /**
     * 请求处理
     * 
     * @param string $url
     * @param array $params
     * @param string $httpMethod
     * @param number $connectTimeout 毫秒数
     * @param number $excuteTimeout 毫秒数
     * @throws \Exception
     * @return array
     */
    protected function _processRequest($url, $params = array(), $httpMethod = self::HTTP_REQUEST_METHOD_GET, $connectTimeout = 500, $excuteTimeout = 2000)
    {
        $result = array();
       
        // http 请求
        if (self::HTTP_REQUEST_METHOD_GET == $httpMethod || self::HTTP_REQUEST_METHOD_GET == strtoupper($httpMethod)) {
            $result = $this->_curl->get($url, $params, array(), $connectTimeout, $excuteTimeout);
        } else {
            $result = $this->_curl->post($url, $params, array(), $connectTimeout, $excuteTimeout);
        }

        // 网络异常
        if ($result === FALSE) {
            self::logRequestException($url, $httpMethod, $params, $result, 'fatal');
            throw new \Exception('请求超时，或网络异常', 50101);
        }
        
        // 返回值非json格式
        $resArray = json_decode($result, TRUE);
        if (! is_array($resArray) || ! isset($resArray[$this->_retStatus])) {
            self::logRequestException($url, $httpMethod, $params, $result, 'fatal');
            throw new \Exception('请求异常，返回值异常', 50102);
        }
        
        // 请求返回错误
        if ($resArray[$this->_retStatus] != 0) {
            self::logRequestException($url, $httpMethod, $params, $result, 'notice');
            throw new \Exception($resArray[$this->_retMsg], $resArray[$this->_retStatus]);
        }

        return $resArray;
    }
    
    /**
     * 请求处理
     *
     * @param string $url
     * @param array $params
     * @param string $httpMethod
     * @param number $connectTimeout 毫秒数
     * @param number $excuteTimeout 毫秒数
     * @throws \Exception
     * @return array
     */
    protected function _processResponse($url, $params = array(), $httpMethod = self::HTTP_REQUEST_METHOD_GET, $connectTimeout = 500, $excuteTimeout = 2000)
    {
        $result = array();
         
        // http 请求
        if (self::HTTP_REQUEST_METHOD_GET == $httpMethod || self::HTTP_REQUEST_METHOD_GET == strtoupper($httpMethod)) {
            $result = $this->_curl->get($url, $params, array(), $connectTimeout, $excuteTimeout);
        } else {
            $result = $this->_curl->post($url, $params, array(), $connectTimeout, $excuteTimeout);
        }
    
        // 网络异常
        if ($result === FALSE) {
            self::logRequestException($url, $httpMethod, $params, $result, 'fatal');
            throw new \Exception('请求超时，或网络异常', 50101);
        }
    
        // 返回值非json格式
        $resArray = json_decode($result, TRUE);
        if (! is_array($resArray) || ! isset($resArray[$this->_retStatus])) {
            self::logRequestException($url, $httpMethod, $params, $result, 'fatal');
            throw new \Exception('请求异常，返回值异常', 50102);
        }
    
        // 请求返回错误
        if ($resArray[$this->_retStatus] != 0) {
            self::logRequestException($url, $httpMethod, $params, $result, 'notice');
            throw new \Exception($resArray[$this->_retMsg], $resArray[$this->_retStatus]);
        }
    
        return $resArray;
    }
} 
