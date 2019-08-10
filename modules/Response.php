<?php
namespace amber\modules;

class Response
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
}