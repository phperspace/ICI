<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 日志 钩子
 *
 * @author Mr.Nobody
 */
class Log
{

    /**
     * 记录request日志
     */
    public function log_request()
    {
        $log = load_class('Log');
        $is_cli = is_cli();
        
        if ($is_cli) {
            global $argv;
            $cli_params = array();
            $cli_params = $argv;
            $uri = "script:" . $cli_params[0];
            array_shift($cli_params);
            $out = array(
                'argv' => $cli_params
            );
        } else {
            $args = array();
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == HTTP_REQUEST_METHOD_POST) {
                $args = $_POST;
            } else {
                $args = $_GET;
            }
            $out = '[mark=request_in][from=' . $_SERVER['REMOTE_ADDR'] . '][args=' . @json_encode($args) . ']';
            $uri = $_SERVER['REQUEST_URI'];
        }
        $arr = explode('?', $uri);
        $log->add_basic(array(
            'uri' => $arr[0]
        ));
        $log->notice(array($out));
        
    }
    
}