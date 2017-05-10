<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| API REQUEST SETTINGS
| -------------------------------------------------------------------
*/
// host
$config['xxx_api']['host'] = 'http://{HOST}/{URI}';
// uri
$config['xxx_api']['uri'] = array(
    'xxx_ooo' => 'xxx/ooo/v1',
);
// 需要做log的api
$config['xxx_api']['request_log'] = array(
    'xxx_ooo',
);


