<?php
namespace amber\modules;
/**
 * http请求
 */
class Http
{
    /**
     * get 请求
     * @param  string  $url
     * @param  integer $timeout
     * @return
     */
    public static function get($url, $timeout = 5)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'timeout' => $timeout,
            )
        ));
        $result = file_get_contents($url, null, $context);
        return $result;
    }

    /**
     * header头为application/x-www-form-urlencode 的 post 请求
     * @param  string  $url     
     * @param  array   $body    
     * @param  integer $timeout 
     * @return 
     */
    public static function post($url, array $args, $timeout = 5)
    {
        $body = http_build_query($args);
        $header = 'Content-Type: application/x-www-form-urlencoded';
        return self::request($url, $body, $header);
    }

    /**
     * json 请求
     * @param  string $url
     * @param  array  $json
     * @param  integer $timeout
     * @return string
     */
    public static function json($url, $json, $timeout = 5)
    {
        $body = json_encode($json, JSON_UNESCAPED_UNICODE);
        $header = 'Content-Type: application/json';
        return self::request($url, $body, $header);
    }

    /**
     * 文本请求
     * @param  string  $url
     * @param  string  $text
     * @param  integer $timeout
     * @return
     */
    public static function text($url, $text, $timeout = 5)
    {
        $header = 'Content-Type: text/plain';
        return self::request($url, $text, $header);
    }

    /**
     * 宽松的POST请求
     * @param  string $url     
     * @param  string  $body    
     * @param  string  $header  
     * @param  integer $timeout 
     * @return string
     */
    public static function request($url, $body, $header, $timeout = 5)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'timeout' => $timeout,
                'header' => $header,
                'content' => $body,
            )
        ));
        $result = file_get_contents($url, null, $context);
        return $result;
    }
}