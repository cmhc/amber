<?php
namespace amber\modules;

class Util
{
    public static function getSiteUrl()
    {
        return (($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim( stripslashes(dirname($_SERVER['PHP_SELF'])), '/');
    }

    /**
     * 判断是否是手机请求
     * @return boolean
     */
    public static function isMobile()
    {
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(
                'nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile',
            );
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }

        }

        if (isset($_SERVER['HTTP_ACCEPT'])) {
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }

        }

        return false;
    }

    /**
     * 获取随机字符串
     * @param  integer $length 长度，默认为8位
     * @return  string
     */
    public static function getRandomString($len = 8, $onlyLetters = false)
    {
        $str = '';
        for( $i=0; $i<$len; $i++ ){
            if ($onlyLetters){
                $num = mt_rand(65,122);
                if ($num >90 && $num < 97) {
                    $num += 7;
                }
                $str .= chr($num);
            } else {
                $str .= chr(mt_rand(33,122));
            }
        }
        return $str;
    }
}
