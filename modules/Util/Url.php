<?php

/**
 * url工具类
 * @Author: huchao
 * @Date:   2019-12-07 21:02:43
 * @Last Modified by:   huchao
 * @Last Modified time: 2019-12-07 21:05:41
 */
namespace amber\modules\Util;

class Url
{
    public static function toAbs($url, $base = null)
    {
        $srcInfo = parse_url($url);
        if (!$base || $srcInfo['scheme']) {
            return $url;
        }

        $baseInfo = parse_url($base);

        if (substr($srcInfo['path'], 0, 1) == '/') {
            return $baseInfo['scheme'] . '://' . $baseInfo['host'] . $srcInfo['path'];
        }

        $rst = array();
        $basePath = array_filter(explode('/', $baseInfo['path']));
        $srcPath = array_filter(explode('/', $srcInfo['path']));
        $newSrcPath = array();
        foreach ($srcPath as $key => $path) {
            if ($path == '..') {
                array_pop($basePath);
            } else if ($path != '.') {
                $newSrcPath[] = $path;
            }
        }
        $absPath = implode('/', array_merge($basePath, $newSrcPath));
        $absUrl = $baseInfo['scheme'] . '://' . $baseInfo['host'] . '/' . $absPath;
        
        if (!empty($srcInfo['query'])) {
            $absUrl .= '?' . $srcInfo['query'];
        }

        return str_replace('\\', '/', $absUrl);
    }

    /**
     * 获取当前页面url
     * @return string
     */
    public static function getCurrentPage()
    {
        $home = (($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim( stripslashes(dirname($_SERVER['PHP_SELF'])), '/');
        $queryString = $_SERVER['REQUEST_URI'];
        return $home . $queryString;
    }
}