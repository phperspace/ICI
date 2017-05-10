<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 日志类
 * 自定义的 log 日志类，未继承框架log类。
 * 兼容原系统日志方法
 *
 * @author Mr.Nobody
 */
class MY_Log
{

    /**
     * 每次请求的日志id
     *
     * @var string
     */
    protected $_log_id;

    /**
     * 日志路径
     *
     * @var string
     */
    protected $_log_path;

    /**
     * 最小记录级别
     *
     * @var int
     */
    protected $_threshold = 1;

    /**
     * 日期格式
     *
     * @var string
     */
    protected $_date_fmt = 'Y-m-d H:i:s';

    /**
     * 日志是否可用
     *
     * @var boolean
     */
    protected $_enabled = TRUE;

    /**
     * 普通日志
     *
     * @var string
     */
    protected static $_logFile = 'app.log';

    /**
     * 错误日志
     *
     * @var string
     */
    protected static $_wfLogFile = 'app.log.wf';

    /**
     * 日志级别
     *
     * @var array
     */
    protected static $_levels = array(
        'FATAL' => 1,
        'ERROR' => 1,
        'WARNING' => 2,
        'NOTICE' => 4,
        'TRACE' => 8,
        'INFO' => 16,
        'DEBUG' => 32,
        'ALL' => 64
    );

    /**
     * 错误日志信息
     *
     * @var string
     */
    protected $_wflog_str = '';

    /**
     * 普通日志信息
     *
     * @var string
     */
    protected $_log_str = '';

    /**
     * 每次写日志的buffer大小
     *
     * @var int
     */
    const PAGE_SIZE = 4096;
    
    /**
     * 公共日志部分，包含logid uri等
     * 在add_basic中更新
     * @var array
     */
    protected $_basic_info_str = '';

    /**
     * 构造方法
     */
    public function __construct()
    {
        $config = & get_config();
        
        $this->_log_path = ($config['log_path'] != '') ? $config['log_path'] : APPPATH . 'logs/';
        
        // 日志目录是否可写
        if (! is_dir($this->_log_path) or ! is_really_writable($this->_log_path)) {
            $this->_enabled = FALSE;
        }
        
        if (is_numeric($config['log_threshold'])) {
            $this->_threshold = $config['log_threshold'];
        }
        
        if ($config['log_date_format'] != '') {
            $this->_date_fmt = $config['log_date_format'];
        }
        
        // 初始化logid
        $time = microtime();
        $this->_log_id = substr($time, 11) . substr($time, 2, 8) . rand(1000, 9999);
    }

    /**
     * 写日志
     * 兼容原系统日志
     *
     * @param string $level            
     * @param string $msg           
     * @param string $flg                
     */
    public function write_log($level = 'error', $msg, $flg = 'undef')
    {
        $level = strtoupper($level);
        if (! isset(self::$_levels[$level])) {
            $this->_enabled = FALSE;
        }
        
        if ($this->_enabled === FALSE) {
            return FALSE;
        }
        
        $format = '[msg=%s]';
        $msg = sprintf($format, $msg);
        $this->_log($level, array(
            $msg
        ));
        return TRUE;
    }

    /**
     * log fatal
     *
     * @param array $args           
     * @param string $flg             
     */
    public function fatal($args, $flg = 'undef')
    {
        $this->_log('FATAL', $args, $flg);
    }

    /**
     * log warning
     *
     * @param array $args           
     * @param string $flg             
     */
    public function warning($args, $flg = 'undef')
    {
        $this->_log('WARNING', $args, $flg);
    }

    /**
     * log notice
     *
     * @param array $args           
     * @param string $flg             
     */
    public function notice($args, $flg = 'undef')
    {
        $this->_log('NOTICE', $args, $flg);
    }

    /**
     * log trace
     *
     * @param array $args             
     * @param string $flg           
     */
    public function trace($args, $flg = 'undef')
    {
        $this->_log('TRACE', $args, $flg);
    }

    /**
     * log debug
     *
     * @param array $args            
     * @param string $flg            
     */
    public function debug($args, $flg = 'undef')
    {
        $this->_log('DEBUG', $args, $flg);
    }

    /**
     * 将buffer里的内容刷入文件
     */
    public function flush()
    {
        $this->_write(true);
    }

    public function add_basic($basic_info)
    {
        $this->_basic_info_str .= '[';
        foreach ($basic_info as $key => $value) {
            $this->_basic_info_str .= $key . '=' . $value . ']';
        }
    }

    /**
     * 记录日志
     *
     * @param string $log_level            
     * @param array  $arr            
     * @param string $flg            
     */
    private function _log($log_level_name, $arr, $flg)
    {
        // 日志已关闭
        if ($this->_enabled === FALSE) {
            return FALSE;
        }
        
        // 日志级别限制
        $log_level = isset(self::$_levels[$log_level_name]) ? self::$_levels[$log_level_name] : 0;
        if ($log_level > $this->_threshold) {
            return FALSE;
        }
        
        // 获取打印日志的文件和行号
        $bt = debug_backtrace();
        $file_line_str = $this->_get_file_line($bt[2]);
        
        // 时间
        $micro = microtime();
        $sec = intval(substr($micro, strpos($micro, " ")));
        $ms = floor($micro * 1000000); // 微妙
                                       
        // 初始化本条日志串
        $str = sprintf("[%s][%s:%-06d][%s][%s][%s]", $log_level_name, date("Y-m-d H:i:s", $sec), $ms, $this->_log_id, $flg, $file_line_str);

        // 初始化basic_info串，包含logid uri等
        $str .= $this->_basic_info_str;
        
        // $arr[0] 可以定义格式
        $format = $arr[0];
        array_shift($arr);
        if (empty($arr)) {
            $str .= $format;
        } else {
            $str .= vsprintf($format, $arr);
        }
        
        // 区分普通日志还是错误日志
        switch ($log_level_name) {
            case 'FATAL':
            case 'ERROR':
            case 'WARNING':
                $this->_wflog_str .= $str . "\n";
                break;
            case 'DEBUG':
            case 'TRACE':
            case 'NOTICE':
            default:
                $this->_log_str .= $str . "\n";
                break;
        }
        
        // 写入buffer
        $this->_write(false);
    }

    /**
     * 取文件名和行
     *
     * @param array $caller            
     */
    private function _get_file_line($caller)
    {
        $file_line = '';
        if (isset($caller['line'])) {
            $file_line = $caller['file'] . ' +' . $caller['line'];
        }
        $func = '';
        if (isset($caller['function'])) {
            $func = '::' . $caller['function'];
            if (isset($caller['class'])) {
                $func = $caller['class'] . $func;
            }
        }
        if (empty($file_line)) {
            $file_line = '?';
        }
        if (empty($func)) {
            $func = '?';
        }
        return "line=$file_line function=$func";
    }

    /**
     * 写文件
     *
     * @param string $log_file            
     * @param string $log_str            
     */
    private function _write_file($log_file, $log_str)
    {
        // file_put_contents($log_file,$log_str,FILE_APPEND);
        $fd = @fopen($log_file, "a+");
        if (is_resource($fd)) {
            fputs($fd, $log_str);
            fclose($fd);
        }
    }

    /**
     * 写入日志文件
     * 仅当强刷或者超过buffer大小时候
     *
     * @param boolean $force_flush 是否强刷
     */
    private function _write($force_flush)
    {
        if ($force_flush || strlen($this->_log_str) > self::PAGE_SIZE) {
            if (! empty($this->_log_str)) {
                $normal_log_path = $this->_log_path . self::$_logFile;
                $this->_write_file($normal_log_path, $this->_log_str);
                $this->_log_str = '';
            }
        }
        
        if ($force_flush || strlen($this->_wflog_str) > self::PAGE_SIZE) {
            if (! empty($this->_wflog_str)) {
                $wflog_path = $this->_log_path . self::$_wfLogFile;
                $this->_write_file($wflog_path, $this->_wflog_str);
                $this->_wflog_str = '';
            }
        }
    }
}
// END Log Class
/* End of file MY_Log.php */
/* Location: ./application/core/MY_Log.php */
