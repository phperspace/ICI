<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * welcome接口规则
 * @author Mr.Nobody
 *
 */

$config['param']['index']['method'] = HTTP_REQUEST_METHOD_GET;
$config['param']['index']['rules'] = array(
    array(
        'field' => 'hello',
        'rules' => 'trim|required|max_length[32]'
    ),
    array(
        'field' => 'arg1',
        'rules' => 'trim|integer|max_length[32]'
    ),
);

