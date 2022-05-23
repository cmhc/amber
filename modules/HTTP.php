<?php

/**
 * http请求封装类
 * @Author: huchao
 * @Date:   2020-04-16 22:41:30
 * @Last Modified by:   huchao
 * @Last Modified time: 2020-04-16 22:50:13
 */

namespace amber\modules;

class HTTP
{
    protected static $responseHeader;

    protected static $proxy = "";

    /**
     * 设置代理 
     */
    public static function setProxy($proxy)
    {
        self::$proxy = $proxy;
    }

    /**
     * 清除proxy 
     */
    public static function clearProxy()
    {
        self::$proxy = "";
    }
    
    /**
     * get 请求
     * @param  string  $url
     * @param  integer $timeout
     * @return
     */
    public static function get($url, $timeout = 30, $header = '')
    {
        if (empty($header)) {
            $header = self::makeNormalHeader();
        }
        $contextOpt = array(
            'http' => array(
                'method' => 'GET',
                'header' => $header,
                'timeout' => $timeout,
            )
        );
        if (!empty(self::$proxy)) {
            $contextOpt['http']['proxy'] = "tcp://" . self::$proxy;
            $contextOpt['http']['request_fulluri'] = true;
            self::clearProxy();
        }

        // https禁用证书
        if (strpos($url, 'https://') !== false) {
            $contextOpt['ssl'] = array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            );
        }

        $context = stream_context_create($contextOpt);
        $result = file_get_contents($url, false, $context);
        self::$responseHeader = $http_response_header;
        return $result;
    }

    /**
     * header头为application/x-www-form-urlencode 的 post 请求
     * @param  string  $url
     * @param  mixed   $args
     * @param  integer $timeout 
     * @return string
     */
    public static function post($url, $args, $timeout = 30, $header = "")
    {
        if (is_array($args)) {
            $body = http_build_query($args);
        } else if (is_string($args)) {
            $body = $args;
        } else {
            return false;
        }
        
        if ($header) {
            $header = rtrim($header, "\r\n");
            $header .= "\r\n";
        }
        $header .= "Content-Type: application/x-www-form-urlencoded";
        return self::request($url, $body, $header, $timeout);
    }

    /**
     * json 请求
     * @param  string $url
     * @param  array  $json
     * @param  integer $timeout
     * @return string
     */
    public static function json($url, array $json, $timeout = 30, $header = "")
    {
        $body = json_encode($json, JSON_UNESCAPED_UNICODE);
        if ($header) {
            $header = rtrim($header, "\r\n");
            $header .= "\r\n";
        }
        $header .= "Content-Type: application/json";
        return self::request($url, $body, $header, $timeout);
    }

    /**
     * 文本请求
     * @param  string  $url
     * @param  string  $text
     * @param  integer $timeout
     * @return
     */
    public static function text($url, $text, $timeout = 30, $header = "")
    {
        if ($header) {
            $header = rtrim($header, "\r\n");
            $header .= "\r\n";
        }
        $header .= "Content-Type: text/plain";
        return self::request($url, $text, $header, $timeout);
    }

    /**
     * 宽松的POST请求
     * @param  string $url     
     * @param  string  $body    
     * @param  string  $header  
     * @param  integer $timeout 
     * @return string
     */
    public static function request($url, $body, $header, $timeout = 30)
    {
        $params = array(
            'http' => array(
                'method' => 'POST',
                'timeout' => $timeout,
                'header' => $header,
                'content' => $body,
            )
        );

        if (!empty(self::$proxy)) {
            $contextOpt['http']['proxy'] = "tcp://" . self::$proxy;
            $contextOpt['http']['request_fulluri'] = true;
            self::clearProxy();
        }

        // https禁用证书
        if (strpos($url, 'https://') !== false) {
            $params['ssl'] = array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            );
        }
        $context = stream_context_create($params);
        $result = file_get_contents($url, null, $context);
        self::$responseHeader = $http_response_header;
        return $result;
    }

    public static function getRawHeader()
    {
        return self::$responseHeader;
    }

    /**
     * 解析header
     */
    public static function getHeader()
    {
       $header = array();
        foreach (self::$responseHeader as $k=>$v) {
            $t = explode(':', $v, 2);
            if (isset($t[1])) {
                $header[strtolower(trim($t[0]))] = trim($t[1]);
            } else {
                $header[] = $v;
                if(preg_match("#HTTP/[0-9\.]+\s+([0-9]+)\s+((\w|\s)+)#",$v, $out)) {
                    $header['code'] = intval($out[1]);
                    $header['msg'] = $out[2];
                }
            }
        }
        //charset
        if (isset($header['content-type']) && strpos($header['content-type'], 'charset') !== false) {
            $charset = substr($header['content-type'], strpos($header['content-type'], 'charset') + 8);
            $header['charset'] = $charset;
        }
        return $header;
    }

    /**
     * 生成通用的http请求头 
     */
    public static function makeNormalHeader()
    {
        $header = array(
            "Pragma: no-cache",
            "Cache-Control: no-cache",
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Accept-Language: zh-CN,zh;q=0.9",
        );
        return implode("\r\n", $header) . "\r\n";
    }
}
