<?php
/**
 * 静态文件处理类
 */
namespace amber\modules\View;
use amber\modules\Route;

class StaticFile
{
    protected static $files;

    public static function add($path, $group)
    {
        self::$files[$group][] = [$path, 'path'];
        return ;
    }

    /**
     * 添加字符串
     */
    public static function addContent($content, $group)
    {
        self::$files[$group][] = [$content, 'content'];
    }

    /**
     * 返回组内所有的文件内容
     */
    public static function get($group)
    {
        if (!isset(self::$files[$group])) {
            return false;
        }
        $content = '';
        foreach (self::$files[$group] as $file) {
            if ($file[1] == 'path') {
                $content .= file_get_contents($file[0]);
            } else if ($file[1] == 'content') {
                $content .= $file;
            }
        }
        return $content;
    }

    public static function clear()
    {
        self::$files = array();
    }
}