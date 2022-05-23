<?php
namespace amber\modules\Util;

/**
 * 图片本地化
 */

class Localize
{

    /**
     * 将文本里面的图片本地化
     * @param  string $content
     * @param  string $pageUrl
     * @param  callable $callback 该方法接受图片内容，需要返回图片的本地化url
     * @return string
     */
    public static function image($content, $pageUrl, $callback)
    {
        $content = stripslashes($content);
        preg_match_all("/<img[^>]*?src=[\"']([^\"']*)[\"'][^>]*>/i", $content, $matches);

        if (empty($matches[0])) {
            return $content;
        }

        $images = array_unique($matches[1]);
        foreach ($images as $image) {
            $absUrl = Url::toAbs($image, $pageUrl);
            $opt = array(
                'http' => array(
                    'timeout' => 10,
                ),
            );
            $context = stream_context_create($opt);
            $file = file_get_contents(trim($absUrl), false, $context);
            $localUrl = call_user_func_array($callback, array($absUrl, $file));
            $content = str_replace($image, $localUrl, $content);
        }
        return $content;
    }
}