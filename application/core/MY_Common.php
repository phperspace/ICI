<?php

if ( ! function_exists('_shutdown_handler'))
{
    /**
     * 进程exit前调用
     * regist_shutdown_hanler
     */
    function _shutdown_handler()
    {
        log_finish();
        $last_error = error_get_last();
        if (isset($last_error) &&
            ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
        {
            _error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
        }
    }
}

if ( ! function_exists('log_finish'))
{
    /**
     * 记录请求结束状态
     */
    function log_finish()
    {
        $log = load_class('Log');
    
        global $BM, $class, $method;
    
        $total_execution_time = $BM->elapsed_time('total_execution_time_start');
    
        $loading_time = $BM->elapsed_time('loading_time:_base_classes_start', 'loading_time:_base_classes_end');
    
        $controller_execution_time = $BM->elapsed_time('controller_execution_time_( ' . $class . ' / ' . $method . ' )_start', 'controller_execution_time_( ' . $class . ' / ' . $method . ' )_end');
    
        $content = vsprintf("[mark=request_out][proc_time=%f][time_total=%f][time_load_base=%f][time_ac_exe=%f (s)][memory_use=%f][memory_peak=%f (MB)]", array(
            $total_execution_time,
            $total_execution_time,
            $loading_time,
            $controller_execution_time,
            memory_get_usage(true) /1024.0 / 1024.0,
            memory_get_peak_usage(true) / 1024.0 / 1024.0
        ));
        $log->notice(array($content), 'log_finish');
        $log->flush();
    }
}


if ( ! function_exists('third_party_autoload'))
{
    
    // 支持匹配规则：a_b 或  a/b 或 a\b
    function third_party_autoload($class){
        
        if (function_exists('__autoload')) {
            //    Register any existing autoloader function with SPL, so we don't get any clashes
            spl_autoload_register('__autoload');
        }
        $file = preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
    
        // 过滤CI类、CI覆盖类
        if (strpos($file, 'CI') === 0 || strpos($file, 'MY') === 0) {
            return ;
        }
        
        // 判断文件是否存在
        $filePath = APPPATH . 'third_party/' . $file;
        if (file_exists($filePath)) {
            require $filePath;
        }
    
    }
}



