<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * cli模式基础类
 *
 * @author Mr.Nobody
 *        
 */
class Base extends CI_Controller
{

    public function __construct()
    {
        // 线上环境只允许cli模式执行接口
        if (ENVIRONMENT == 'production' && php_sapi_name() !== 'cli') {
            exit('please running in CLI');
        }
        
        // 脚本超时设为不限
        set_time_limit(0);
        
        // 初始化REMOTE_ADDR
        if (! isset($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        }
        
        // 父类构造函数
        parent::__construct();
        
        $this->load->helper('common');
        
    }
    
}

/* End of file Base.php */
/* Location: ./app/modules/cli/controllers/Base.php */
