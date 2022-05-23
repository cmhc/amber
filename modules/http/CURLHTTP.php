<?php
namespace amber\modules\http;

class CURLHTTP extends HTTP
{
    /**
     * 执行
     */
    public function exec()
    {
        $ch = curl_init();
        $url = parent::getURL();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, parent::getTimeout());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, parent::getRequestHeader());
        // curl_setopt($ch, CURLOPT_ENCODING, ""); // 表示接受所有编码，可以自动解压gzip内容

        // 允许重定向
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 跟踪跳转，对于301，302游泳
        curl_setopt($ch, CURLOPT_COOKIEFILE, ""); // 跳转时候带上cookie

        // 不检查https证书
        if (strpos($url, 'https') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // cookie
        $cookie = parent::getRequestCookie();
        if ($cookie != "") {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }

        if (parent::isPOST()) {
            curl_setopt($ch, CURLOPT_POST, true);
            $postData = parent::getPostData();
            if (is_array($postData) || is_object($postData)) {
                $postData = http_build_query($postData);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        } else if (parent::isHEAD()) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        // // 开发模式 {
        // $fp = fopen(__DIR__ . "/curl_debug.log", 'a+');
        // curl_setopt($ch, CURLOPT_VERBOSE, true);
        // curl_setopt($ch, CURLOPT_STDERR, $fp);
        // // }

        $this->initProxy($ch);
        $content = curl_exec($ch);
        if (!$content) {
            parent::setError(curl_error($ch));
            return "";
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        parent::setResponseHeader(substr($content, 0, $headerSize));

        // 获取跳转之后的真实url地址，以便后续获取
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        parent::setRealURL($url);

        return substr($content, $headerSize);
    }

    /**
     * 设置代理 
     */
    public function initProxy($ch)
    {
        $proxy = parent::getProxy();
        if ($proxy["type"] == "") {
            return ;
        }
        curl_setopt($ch, CURLOPT_PROXY, $proxy["proxy"]);
        if ($proxy["type"] == "http") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        } else if ($proxy["type"] == "socks4") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
        } else if ($proxy["type"] == "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
        }
    }

}