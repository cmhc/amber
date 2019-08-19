<?php
namespace amber\modules;

/**
 * 日志记录器
 */

class Logger
{
    /**
     * 常规日志记录
     * @var array
     */
    protected static $logger = array();

    /**
     * 自动切割日志记录
     * @var array
     */
    protected static $rotation = array();

    /**
     * 常规日志记录器
     * @param  array $config
     * @return Logger\File
     */
    public static function instance($config)
    {
        $key = md5(json_encode($config));
        if (!isset(self::$logger[$key])) {
            self::$logger[$key] =  new Logger\File($config);
        }
        return self::$logger[$key];
    }

    /**
     * 带有自动切割日志的日志记录器
     * @param  array $config
     * @return Logger\RotateFile
     */
    public static function instanceRotation($config)
    {
        $key = md5(json_encode($config));
        if (!isset(self::$rotation[$key])) {
            $FileLogger = new Logger\File($config);
            self::$rotation[$key] = new Logger\RotateFile($FileLogger);
        }
        return self::$rotation[$key];
    }
}