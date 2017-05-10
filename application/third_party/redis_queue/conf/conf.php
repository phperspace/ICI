<?php
if (! defined('PHPER_SPACE_TASK_QEUE_MARK')) exit('No direct script access allowed');

/* 配置数组 */
$phper_space_task_queue_config                                  = array();

/* 
 * Redis的Key前缀
 */
$phper_space_task_queue_config['redis']['prefix']               = 'phper_space_task_queue_';

/* End of file conf.php */
/* Location: /conf/conf.php */