<?php
/**
 * 微信公众号工具 
 */
namespace amber\modules\Util;

class WeiXin
{
    private static $token;

    public static function setToken($token)
    {
        self::$token = $token;
    }

    /**
     * 检查签名
     * @param  string $signature
     * @param  string $timestamp
     * @param  string $nonce
     * @return boolean
     */
    public static function checkSignature($signature, $timestamp, $nonce)
    {
        $tmpArr = array(self::$token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        if (sha1(implode($tmpArr)) == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 发送消息
     * @param string $to
     * @param string $from
     * @param string $content
     */
    public static function sendTextMessage($to, $from, $content)
    {
        $xml = "<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%d</CreateTime>
  <MsgType><![CDATA[text]]></MsgType>
  <Content><![CDATA[%s]]></Content>
  <MsgId>%d</MsgId>
</xml>";
        $result = sprintf($xml, $to, $from, time(), $content, time());
        return $result;
    }

}