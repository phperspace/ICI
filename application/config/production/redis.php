<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| REDIS CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
*/

//default
$config['redis']['default']['host'] = '127.0.0.1';
$config['redis']['default']['port'] = 6379;
$config['redis']['default']['password'] = 'admin';
$config['redis']['default']['database'] = 0;

//thirdpart_map_gamesdk_id
$config['redis']['aof']['host'] = '127.0.0.1';
$config['redis']['aof']['port'] = 6379;
$config['redis']['aof']['password'] = 'admin';
$config['redis']['aof']['database'] = 0;



/* End of file redis.php */
/* Location: ./application/config/redis.php */