<?php
namespace app\controller;

class Base
{
    public function __construct()
    {
        
    }

    /**
     * 输出禁止
     * @return
     */
    protected function forbidden()
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
    protected function notFound($url, $v='v1')
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
    protected function success($data, $v='v1')
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
     * json输出
     * @param  array $data
     */
    protected function output($data)
    {
        echo json_encode($data);
        return ;
    }

    /**
     * 输入转换成int
     * @param  string  $key
     * @param  integer $default
     */
    protected function toInt($key, $default=0)
    {
        if (isset($_REQUEST[$key])) {
            return intval($_REQUEST[$key]);
        }
        return $default;
    }

    /**
     * 输入转换成string
     * @param  string $key     
     * @param  string $default 
     * @return string
     */
    protected function toStr($key, $default='')
    {
        if (isset($_REQUEST[$key])) {
            return trim(strval($_REQUEST[$key]));
        }
        return $default;
    }

    protected function toArr($key, $default = array())
    {
        if (isset($_REQUEST[$key]) && is_array($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        return $default;
    }
}