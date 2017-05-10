<?php

/**
 * Curl类
 * 
 * @author Mr.Nobody
 */
class Curl
{

    /**
     * 提交GET请求
     * 
     * @param string    $url       请求url地址
     * @param mixed     $data      GET数据,数组或类似id=1&k1=v1
     * @param array     $header    头信息
     * @param int       $timeout   超时时间
     * @param int       $port      端口号
     * @return array               请求结果,
     *                             如果出错,返回结果为array('error'=>'','result'=>''),
     *                             未出错，返回结果为array('result'=>''),
     */
    public  function get($url, $data = array(), $header = array(), $connect_timeout = 5000, $excute_timeout = 5000)
    {
        $ch = curl_init();
        if (!empty($data)) {
            $data = is_array($data)?http_build_query($data): $data;
            $url .= (strpos($url,'?')?  '&': "?") . $data;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $excute_timeout);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        ! empty ($header) && curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        
        // curl 1000ms bug http://www.laruence.com/2014/01/21/2939.html                                                                                       
        if ($connect_timeout <= 1000 || $excute_timeout <= 1000) {
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }
        
        $result = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        
        if (0 != curl_errno($ch)) {
            write_fatal(array(
                'errmsg' => 'CURL_ERROR',
                'type' => 'get',
                'errno' => curl_errno($ch),
                'proc_time' => $curlInfo['total_time'],
                'curlinfo' => $curlInfo,
                'info' => curl_error($ch)
            ), 'curl_failed');
            return FALSE;
        }
        
        write_notice(array(
            'errmsg' => 'CURL_INFO',
            'type' => 'get',
            'errno' => curl_errno($ch),
            'proc_time' => $curlInfo['total_time'],
            'curlinfo' => $curlInfo,
            'request' => $url,
            'return' => $result
        ), 'curl_success');
        curl_close($ch);
        return $result;
    }
    /**
     * 提交POST请求
     * 
     * @param string    $url       请求url地址
     * @param mixed     $data      POST数据,数组或类似id=1&k1=v1
     * @param array     $header    头信息
     * @param int       $timeout   超时时间
     * @param int       $port      端口号
     * @return string              请求结果,
     *                             如果出错,返回结果为array('error'=>'','result'=>''),
     *                             未出错，返回结果为array('result'=>''),
     */
    public  function post($url, $data = array(), $header = array(), $connect_timeout = 5000, $excute_timeout = 5000)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $excute_timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        ! empty ($header) && curl_setopt($ch, CURLOPT_HTTPHEADER, $header);


        // curl 1000ms bug http://www.laruence.com/2014/01/21/2939.html                                                                                       
        if ($connect_timeout <= 1000 || $excute_timeout <= 1000)
        {
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }

        $result = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        if (0 != curl_errno($ch)) {
            write_fatal(array(
                'errmsg' => 'CURL_ERROR',
                'type' => 'post',
                'errno' => curl_errno($ch),
                'proc_time' => $curlInfo['total_time'],
                'curlinfo' => $curlInfo,
                'info' => curl_error($ch)
            ), 'curl_failed');
            return FALSE;
        }
        write_notice(array(
            'errmsg' => 'CURL_INFO',
            'type' => 'post',
            'errno' => curl_errno($ch),
            'proc_time' => $curlInfo['total_time'],
            'curlinfo' => $curlInfo,
            'request' => $url . json_encode($data),
            'return' => $result
        ), 'curl_success');
        curl_close($ch);

        return $result;
    }
    
    /**
     * 并发执行GET请求
     *
     * @param   array  $nodes           url数组，例如 array("a.com?a=1&b=1", "a.com?a=2&b=2");
     * @param   array  $header          header数组
     * @param   number $connectTimeout  连接超时时间
     * @param   number $excuteTimeout   执行超时时间
     * @return  array  $result          执行结果数组，例如 array("result_string", "result_string");
     */
    function mutiGet($nodes, $header = array(), $connectTimeout = 1000, $excuteTimeout = 1000)
    {
        $header = array();
    
        $mh = curl_multi_init();
        $curl_array = array();
    
        foreach ($nodes as $i => $url) {
            $curl_array[$i] = curl_init($url);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT_MS, $connectTimeout);
            curl_setopt($curl_array[$i], CURLOPT_TIMEOUT_MS, $excuteTimeout);
            curl_setopt($curl_array[$i], CURLOPT_POST, 0);
            curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYPEER, FALSE);
            if ($connectTimeout <= 1000 || $excuteTimeout <= 1000) {
                curl_setopt($curl_array[$i], CURLOPT_NOSIGNAL, 1);
            }
    
            curl_multi_add_handle($mh, $curl_array[$i]);
        }
    
        $running = NULL;
        do {
            usleep(10);
            curl_multi_exec($mh, $running);
        } while ($running > 0);
    
        $result = array();
        foreach ($nodes as $i => $url) {
            $result[$i] = curl_multi_getcontent($curl_array[$i]);
        }
    
        foreach ($nodes as $i => $url) {
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);
        
        write_notice(array(
            'errmsg' => 'CURL_INFO',
            'type' => 'muti_get',
            'nodes' => $nodes,
            'result' => $result
        ), 'curl_muti_get_result');
        return $result;
    }
    
}
