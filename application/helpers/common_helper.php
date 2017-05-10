<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

if (! function_exists('get_args_by_keys')) {

    function get_args_by_keys($args, $keys)
    {
        extract($args);
        return compact($keys);
    }
}


if (! function_exists('echo_debug_msg')) {

    /**
     * 打印调试信息
     *
     * @author Mr.Nobody
     * @param string $msg
     */
    function echo_debug_msg($msg)
    {
        echo get_format_mic_timestamp(), " | {$msg} |<br/> \r\n";
    }
}

if (! function_exists('base64url_encode')) {

    /**
     * base64url_encode
     *
     * @author Mr.Nobody
     * @param string $data
     * @return string
     */
    function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (! function_exists('base64url_decode')) {

    /**
     * base64url_decode
     *
     * @author Mr.Nobody
     * @param string $data
     * @return string
     */
    function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

if (! function_exists('get_distance')) {

    /**
     * 根据两点间的经纬度计算距离
     *
     * @author Mr.Nobody
     * @param number $lat1
     * @param number $lng1
     * @param number $lat2
     * @param number $lng2
     * @return number
     */
    function get_distance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000; // approximate radius of earth in meters
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }
}

if (! function_exists('get_format_mic_timestamp')) {

    /**
     * 取一个格式化的毫秒时间戳
     * @author Mr.Nobody
     */
    function get_format_mic_timestamp()
    {
        $micro = microtime();
        $sec = intval(substr($micro, strpos($micro, " ")));
        $ms = floor($micro * 1000); // 毫秒
        return sprintf('%s.%3d', date('Y-m-d H:i:s', $sec), $ms);
    }
}


if (! function_exists('get_sign')) {

    /**
     * 计算签名
     * @author Mr.Nobody
     */
    function get_sign($key, $params, &$str, $signMethod = 'md5')
    {
        unset($params['sign']);
        $params['sign_key'] = $key;

        ksort($params);

        $str = '';

        foreach ($params as $k => $v) {
            if (is_array($v)) {
                isset($_REQUEST[$k]) && $v = $_REQUEST[$k];
                $v = json_encode($v);
            }
            if ('' == $str) {
                $str .= $k . '=' . trim($v);
            } else {
                $str .= '&' . $k . '=' . trim($v);
            }
        }
        return $signMethod($str);
    }
}


