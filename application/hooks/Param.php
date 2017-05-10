<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 参数检查 钩子
 * @author Mr.Nobody
 */
class Param
{

    /**
     * 魔术方法
     * 获取CI对象的属性
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        $CI = & get_instance();
        return $CI->$key;
    }

    /**
     * 魔术方法
     * 设置CI对象的属性
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public function __set($key, $val)
    {
        $CI = & get_instance();
        $CI->$key = $val;
    }

    /**
     * 参数验证
     */
    public function validate()
    {
        $paramConfPath = 'param/' . $this->router->directory . lcfirst($this->router->class);
        if (! file_exists(APPPATH . 'config/' . $paramConfPath . '.php')) {
            $this->_iniParams();
            return;
        }
        $this->config->load($paramConfPath);
        $paramConf = $this->config->item('param');
        $interface = $this->router->method;     

        $method = isset($paramConf[$interface]['method']) ? $paramConf[$interface]['method'] : '';
        $this->_iniParams($method);
        
        if (isset($paramConf[$interface])) {
            
            $result = $this->_check($paramConf[$interface]['rules']);

            // 若检查失败，返回错误，并退出
            if (! $result) {
                $search = array("\n", "\r", '</p>', '<p>');
                $errorMsg = str_replace($search, ' ', $this->form_validation->error_string());
                $this->_handleValidateFailed($errorMsg);
            }
            
        } 
    }


    /**
     * 参数检查
     */
    protected function _check($rules)
    {
        // 检查
        $this->load->library('Form_validation');
        $this->form_validation->set_data($this->params);
        $this->form_validation->set_rules($rules);
        $result = $this->form_validation->run();

        // 回设参数
        $this->params = $this->form_validation->validation_data;

        return $result;
    }
    
    /**
     * 参数初始化
     * 
     * @param string $method
     */
    protected function _iniParams($method = '')
    {
        $params = array();
        
        if (! $method) {
            if ($this->input->get()) {
                $params += (array)$this->input->get(NULL, TRUE);
            }
            if ($this->input->post()) {
                $params += (array)$this->input->post(NULL, TRUE);
            }
        } elseif ($method == HTTP_REQUEST_METHOD_GET) {
            $params = (array)$this->input->get(NULL, TRUE);
        } elseif ($method == HTTP_REQUEST_METHOD_POST){
            $params = (array)$this->input->post(NULL, TRUE);
        }
        
        // 回设controller的参数数组
        $this->params = $params;
        
    }

    /**
     * 记录日志
     *
     * @param string $error
     */
    protected function _handleValidateFailed($error)
    {
        $this->stdreturn->failed('4001', $error);
    }

}

/* End of file Param.php */
/* Location: ./application/hooks/Param.php */