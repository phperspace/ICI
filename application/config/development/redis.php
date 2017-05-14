<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| REDIS CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
*/

$config['redis']['default'] = array(
    0 => array(
        'host' => '127.0.0.1',
        'port' => '6379',
        'weight' => '1',
        'lasting' => 1,
        'connectTime' => 1,
        'db' => 0,
        'password' => '',
    ),
    1 => array(
        'host' => '127.0.0.1',
        'port' => '6379',
        'weight' => '1',
        'lasting' => 1,
        'connectTime' => 1,
        'db' => 0,
        'password' => '',
    )
);

$config['redis']['host1'] = array(
    0 => array(
        'host' => '127.0.0.1',
        'port' => '6379',
        'weight' => '1',
        'lasting' => 1,
        'connectTime' => 1,
        'db' => 0,
        'password' => '',
    ),
    1 => array(
        'host' => '127.0.0.1',
        'port' => '6379',
        'weight' => '1',
        'lasting' => 1,
        'connectTime' => 1,
        'db' => 0,
        'password' => '',
    )
);




/* End of file redis.php */
/* Location: ./application/config/redis.php */