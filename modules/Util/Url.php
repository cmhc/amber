<?php
namespace amber\modules\Util;

/**
 * 链接处理类
 */

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
}