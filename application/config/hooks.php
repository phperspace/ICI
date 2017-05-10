<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * | -------------------------------------------------------------------------
 * | Hooks
 * | -------------------------------------------------------------------------
 * | This file lets you define "hooks" to extend CI without hacking the core
 * | files. Please see the user guide for info:
 * |
 * | https://codeigniter.com/user_guide/general/hooks.html
 * |
 */

// 请求日志
$hook['pre_controller'][] = array(
    'class' => 'Log',
    'function' => 'log_request',
    'filename' => 'Log.php',
    'filepath' => 'hooks'
);

// 参数检查
$hook['post_controller_constructor'][] = array(
    'class' => 'Param',
    'function' => 'validate',
    'filename' => 'Param.php',
    'filepath' => 'hooks'
);