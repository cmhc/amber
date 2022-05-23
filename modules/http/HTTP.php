<?php

namespace amber\modules\http;

abstract class HTTP
{
    private $url = "";

    private $timeout = 30;

    /**
     * 请求头
     * @var array
     */
    private $requestHeader = array();

    private $responseRawHeader = "";

    /**
     * @var array 
     */
    private $responseHeader = array();

    /**
     * @var string 
     */
    private $requestCookie = "";

    private $method = "GET";

    private $realURL = "";

    /**
     * 代理通道
     * 123.123.123.123:1080 
     */
    private $proxy;

    private $proxyType;

    private $postData;

    /**
     * 错误信息 
     */
    private $error = "";

    /**
     * 返回结果，网络驱动需要实现的方式 
     */
    public abstract function exec();

    public static function get($url, $timeout = 30)
    {
        $instance = new static();
        $instance->setURL($url);
        $instance->setTimeout($timeout);
        $instance->method = "GET";
        return $instance;
    }

    public static function post($url, $data, $timeout = 30)
    {
        $instance = new static();
        $instance->setURL($url);
        $instance->setPostData($data);
        $instance->setTimeout($timeout);
        $instance->method = "POST";
        return $instance;
    }

    public static function head($url, $timeout = 30)
    {
        $instance = new static();
        $instance->setURL($url);
        $instance->setTimeout($timeout);
        $instance->method = "HEAD";
        return $instance;
    }

    /**
     * @param string url
     * @param string $data
     * @param int $timeout
     */
    public static function json($url, $data, $timeout = 30)
    {
        $data = json_encode($data);
        $instance = self::post($url, $data, $timeout);
        $instance->setRequestHeader(array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ));
        return $instance;
    }

    public function isGET()
    {
        return $this->method == "GET" ? true : false;
    }

    protected function isPOST()
    {
        return $this->method == "POST";
    }

    protected function isHEAD()
    {
        return $this->method == "HEAD";
    }

    /**
     * 获取真实url
     */
    public function getRealURL()
    {
        return $this->realURL;
    }

    /**
     * 设置访问的url 
     */
    public function setURL($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 设置超时时长 
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setPostData($data)
    {
        $this->postData = $data;
        return $this;
    }

    /**
     * 设置请求头 
     */
    public function setRequestHeader(array $header)
    {
        $this->requestHeader = array_merge($this->requestHeader, $header);
        return $this;
    }

    /**
     * 获取响应头
     */
    public function getResponseHeader()
    {
        return $this->responseHeader;
    }

    public function setRequestCookie($cookie)
    {
        $this->requestCookie = $cookie;
        return $this;
    }

    protected function getRequestCookie()
    {
        return $this->requestCookie;
    }

    public function setHTTPProxy($proxy)
    {
        $this->proxy = $proxy;
        $this->proxyType = "http";
        return $this;
    }

    public function setSocks4Proxy($proxy)
    {
        $this->proxy = $proxy;
        $this->proxyType = "socks4";
        return $this;
    }

    public function setSocks5Proxy($proxy)
    {
        $this->proxy = $proxy;
        $this->proxyType = "socks5";
        return $this;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置代理
     * http代理格式 http://127.0.0.1:8080
     * socks5代理格式 socks5://127.0.0.1:8080
     * socks4代理格式 socks4://127.0.0.1:8080
     */
    public function setProxy($proxy)
    {
        $items = parse_url($proxy);
        if (empty($items["scheme"]) || empty($items["host"]) || empty($items["port"])) {
            return $this;
        }

        if ($items["scheme"] == "http") {
            $this->setHTTPProxy($items["host"] . ":" . $items["port"]);
        } else if ($items["scheme"] == "socks4") {
            $this->setSocks4Proxy($items["host"] . ":" . $items["port"]);
        } else if ($items["scheme"] == "socks5") {
            $this->setSocks5Proxy($items["host"] . ":" . $items["port"]);
        }
        
        return $this;
    }

    /**
     * 获取当前代理 
     */
    protected function getProxy()
    {
        return array(
            'proxy' => $this->proxy,
            'type' => $this->proxyType
        );
    }

    /**
     * 获取原始响应头 
     */
    public function getResponseRawHeader()
    {
        return $this->responseRawHeader;
    }

    /**
     * 设置原始响应头 
     */
    public function setResponseHeader($responseHeader)
    {
        $this->responseRawHeader = $responseHeader;
        $this->responseHeader = $this->parseHeader($responseHeader);
    }

    /**
     * 将文本header解析成KV格式 
     */
    private function parseHeader($str)
    {
        $responseHeader = array_filter(explode("\r\n", $str));
        if (count($responseHeader) == 0) {
            return array();
        }

        $header = array();
        if (preg_match("#http/[0-9\.]+\s+([0-9]+)\s+((\w|\s)+)#i", $responseHeader[0], $matches)) {
            $header['code'] = intval($matches[1]);
            $header['msg'] = $matches[2];
        }

        foreach ($responseHeader as $k => $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1])) {
                $key = strtolower(trim($t[0]));
                $value = trim($t[1]);
                // value 有多个值的情况，比如cookie
                if (isset($header[$key])) {
                    if (!is_array($header[$key])) {
                        $header[$key] = array($header[$key]);
                    }
                    $header[$key][] = $value;
                } else {
                    $header[$key] = $value;
                }
            } else {
                $header[] = $v;
            }
        }

        //charset
        // file_put_contents(__DIR__ . "/log", json_encode($header['content-type']));
        if (isset($header['content-type']) && strpos($header['content-type'], 'charset') !== false) {
            $charset = substr($header['content-type'], strpos($header['content-type'], 'charset') + 8);
            $header['charset'] = $charset;
        }
        return $header;
    }

    /**
     * 请求完成之后写入真实url 
     */
    protected function setRealURL($url)
    {
        $this->realURL = $url;
    }

    /**
     * 获取访问的url 
     */
    protected function getURL()
    {
        return $this->url;
    }

    protected function getPostData()
    {
        return $this->postData;
    }

    /**
     * 获取超时时长 
     */
    protected function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * 获取请求头 
     */
    protected function getRequestHeader()
    {
        return $this->requestHeader;
    }

    protected function setError($error)
    {
        $this->error = $error;
    }

}
