<?php

/**
 * 标准返回类
 * 
 * 1、response的数据格式为json格式（Content-Type:application/json）
 * 2、接口返回三元组(status, msg, data)，如果不符合要求，请自行修改。
 * 3、支持jsonp，前端需要在GET里传入jsonpCallback参数
 * 
 * @author Mr.Nobody
 */
class StdReturn
{
    
    /**
     * 预设三元组(status, msg, data)的key
     * 如果不符合要求，请自行修改
     */
    protected static $_statusKey = "status";
    protected static $_msgKey = "msg";
    protected static $_dataKey = "data";
    
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

    /**
     * 成功返回
     * 
     * @param array $data
     * @param string $exit
     * @param string $logLevel
     */
    public function ok($data = array(), $exit = TRUE, $logLevel = 'ALL')
    {
        // 组装三元组
        $decorate = array(
            self::$_statusKey => 0,
            self::$_msgKey => 'SUCCESS',
            self::$_dataKey => $data,
        );          
        $decorate = json_encode($decorate);    
        
        // 如需要，进行jsonp修饰    
        $this->_jsonpDecorate($decorate);  

        // 记录日志
        $this->_reponseLog($logLevel, $decorate);

        // json格式
        header('Content-Type:application/json');
        
        // 退出执行
        if ($exit) {
            exit($decorate);
        }
        
        // 不退出执行
        $this->output->set_output($decorate);
        return $decorate;
    }
    
    /**
     * 失败返回
     * 
     * @param int $ret
     * @param string $append
     * @param string $exit
     * @param string $logLevel
     */
    public function failed($ret, $append = '', $exit = TRUE, $logLevel = 'notice')
    {
        // 根据错误码去查错误提示
        $this->lang->load('myerror');
        $msg = '';
        if (isset($this->lang->language['myerror'][$ret])) {
            $msg = $this->lang->language['myerror'][$ret];
        }
    
        // 组装三元组
        $decorate = array(
            self::$_statusKey => $ret,
            self::$_msgKey => $msg,
            self::$_dataKey => NULL
        );
        if ($append) {
            $decorate[self::$_msgKey] .= '(' . $append . ')';
        }
        $decorate = json_encode($decorate);

        // 记录日志
        $this->_reponseLog($logLevel, $decorate);
    
        // json格式
        header('Content-Type:application/json');
        
        // 退出执行
        if ($exit) {
            exit($decorate);
        }
        
        // 不退出执行
        $this->output->set_output($decorate);
        return $decorate;
    }
    
    
    /**
     * 记录response日志
     * 
     * @param string $level
     * @param array $decorate
     */
    protected function _reponseLog($level, $decorate)
    {
        $_log = & load_class('Log');
        if ($_log) {
            $_log->write_log($level, $decorate);
        }
    }
    
    /**
     * jsonp修饰
     * 
     * @param string $jsonData
     */
    protected function _jsonpDecorate(&$jsonData) 
    {
        $jsonp = $this->input->get('jsonpCallback', TRUE);
        if ($jsonp) {
            $jsonData = $jsonp . '(' . $jsonData. ')';
        }
    }
    
}

/* End of file stdreturn.php */
/* Location: ./application/libaries/stdreturn.php */