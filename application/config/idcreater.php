<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 *---------------------------------------------------------------
 * define MACHAINEID
 *---------------------------------------------------------------
 *
 * 范围:0-1024
 * 尽量通过配置环境变脸搞定，而不是在代码层配置。
 * 如服务器是nginx，配置环境变量: fastcgi_param MACHAINEID=879;
 * 
 */

$config['idcreater']['machine_id'] = isset($_SERVER['MACHAINEID']) ? $_SERVER['MACHAINEID'] : 0;

/*
 *---------------------------------------------------------------
 * 本地自增Id的文件路径
 *---------------------------------------------------------------
 *
 * 建议放在var目录下
 * 注意文件路径需要对php进程所有者开放写权限。
 * 
 */

$config['idcreater']['auto_inc_id_file_path'] = ROOTPATH . 'var/idcreater/auto_inc_id' ;



