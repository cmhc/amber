<?php
namespace amber\modules;

class IO
{
    /**
     * json输出
     * @param  array $data
     */
    public static function json($data)
    {
        echo json_encode($data);
        return ;
    }
    
    /**
     * 输出禁止信息
     * @return
     */
    public static function forbidden()
    {
        $res = array(
            'version' => 'v1',
            'code' => 403,
            'data' => array(),
            'msg' => 'forbidden'
            );
        echo json_encode($res);
        return ;
    }

    /**
     * 输出url未找到信息
     * @param string $url
     * @param string $v
     */
    public static function notFound($url, $v = 'v1')
    {
        $res = array(
            'version' => $v,
            'code' => 404,
            'data' => array(),
            'msg' => "请求{$url}失败",
            );
        echo json_encode($res);
        return ;
    }

    /**
     * 一个成功的请求返回的信息
     * @param  array $data 
     * @param  string $v
     * @return 
     */
    public static function success($data = array(), $v = 'v1')
    {
        $res = array(
            'version' => $v,
            'code' => 200,
            'data' => $data,
            'msg' => "成功",
            );
        echo json_encode($res);
        return ;
    }

    /**
     * 通用的失败返回
     * @param  string $msg
     * @param  string $v
     * @return 
     */
    public static function failure($msg = '失败', $v = 'v1')
    {
        $res = array(
            'version' => $v,
            'code' => 0,
            'msg' => $msg,
            );
        echo json_encode($res);
        return ;
    }

    /**
     * 输入转换成int
     * @param  string  $key
     * @param  integer $default
     */
    public static function toInt($key, $default=0)
    {
        if (isset($_REQUEST[$key])) {
            return intval($_REQUEST[$key]);
        }
        return $default;
    }

    /**
     * 输入转换成float
     * @param  string  $key
     * @param  integer $default
     */
    public static function toFloat($key, $default=.0)
    {
        if (isset($_REQUEST[$key])) {
            return floatval($_REQUEST[$key]);
        }
        return $default;
    }

    /**
     * 输入转换成string
     * @param  string $key
     * @param  string $default 
     * @return string
     */
    public static function toStr($key, $default='')
    {
        if (isset($_REQUEST[$key])) {
            return trim(strval($_REQUEST[$key]));
        }
        return $default;
    }

    /**
     * 防止xss攻击
     * @param  $value
     * @return
     */
    public static function xss($value)
    {
        return htmlspecialchars($value);
    }

    /**
     * 转化成数组
     * @param  string $key
     * @param  array  $default
     * @return
     */
    public static function toArr($key, $default = array())
    {
        if (isset($_REQUEST[$key]) && is_array($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        return $default;
    }
}