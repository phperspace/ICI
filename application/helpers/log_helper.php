<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 输出文件日志 - notice
 */
if (! function_exists('write_notice')) {

    function write_notice($content, $flag)
    {
        if (! $content) {
            return false;
        }
        
        $_log = & load_class('Log');
        if ($_log) {
            $_log->write_log('notice', $content, $flag);
        }
    }
}

/**
 * 输出文件日志 - warning
 */
if (! function_exists('write_warning')) {

    function write_warning($content, $flag)
    {
        if (! $content) {
            return false;
        }
        
        $_log = & load_class('Log');
        if ($_log) {
            $_log->write_log('warning', $content, $flag);
        }
    }
}

/**
 * 输出文件日志 - fatal
 */
if (! function_exists('write_fatal')) {

    function write_fatal($content, $flag)
    {
        if (! $content) {
            return false;
        }
        
        $_log = & load_class('Log');
        if ($_log) {
            $_log->write_log('fatal', $content, $flag);
        }
    }
}




