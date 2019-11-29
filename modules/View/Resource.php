<?php

/**
 * 静态资源处理类
 * @Author: huchao
 * @Date:   2019-11-26 23:19:16
 * @Last Modified by:   huchao
 * @Last Modified time: 2019-11-26 23:35:28
 */

namespace amber\modules\View;

class Resource
{
    protected static $files = array();

    /**
     * 路径类型
     */
    const TYPE_PATH = 1;

    /**
     * 内容类型
     */
    const TYPE_CONTENT = 2;

    /**
     * 添加文件到group中
     * @param string $realPath
     * @param string $fileName
     * @return  void
     */
    public static function add($realPath, $fileName)
    {
        self::$files[$fileName][] = array($realPath, self::TYPE_PATH);
    }

    /**
     * 添加内容到相应的文件中
     * @param string $content
     * @param string $fileName
     * @return  void 
     */
    public static function addContent($content, $fileName)
    {
        self::$files[$fileName][] = array($content, self::TYPE_CONTENT);
    }

    /**
     * 返回group中的文件内容
     * @param  string $group
     * @return string
     */
    public static function get($fileName)
    {
        if (!isset(self::$files[$fileName])) {
            return false;
        }
        $content = '';
        foreach (self::$files[$fileName] as $file) {
            if ($file[1] == self::TYPE_PATH) {
                $content .= file_get_contents($file[0]);
            } else if ($file[1] == self::TYPE_CONTENT) {
                $content .= $file;
            }
        }
        return $content;
    }

    /**
     * 清空file
     * @return void
     */
    public static function clear()
    {
        self::$files = array();
    }

    /**
     * 输出内容
     * @return void
     */
    public static function render($fileName)
    {
        $content = self::get($fileName);
        if (!$content) {
            header("HTTP/1.1 404 Not Found");
            return ;
        }

        $component = explode('.', $fileName);
        $ext = end($component);
        switch ($ext) {
            case 'js':
                $contentType = 'application/javascript';
                break;
            case 'css':
                $contentType = 'text/css';
                break;
            case 'png':
                $contentType = 'application/png';
                break;
            case 'jpg':
                $contentType = 'application/jpeg';
                break;
            default:
                $contentType = 'text/html';
                break;
        }
        header("Content-Type: {$contentType}");
        echo $content;
    }
}