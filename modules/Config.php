<?php
/**
 * 配置类
 * 可以直接使用set操作设定配置信息
 * 也可以为配置单独存放一个配置文件，使用getf才读取
 */
namespace amber\modules;

class Config
{

    private static $config;

    protected static $configFile;

    /**
     * 配置文件所在路径
     * @var string
     */
    protected static $configDir;

    /**
     * get config
     * @param  string $key
     * @return mixed
     */
    public static function get($key)
    {
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        } else {
            return false;
        }
    }


    /**
     * 添加配置信息
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        if (!isset(self::$config[$key])) {
            self::$config[$key] = $value;
        } else {
            throw new \Exception("config $key is exists", 1);
        }
    }

    /**
     * 设置配置文件所在的文件夹
     * @param string $dir 文件夹
     */
    public static function setConfigDir($dir) 
    {
        self::$configDir = rtrim($dir, '/');
    }

    /**
     * get config file
     * @param $key
     */
    public static function getf($key)
    {
        if (isset(self::$configFile[$key]) && file_exists(self::$configFile[$key])) {
            return require self::$configFile[$key];
        }
        if (empty(self::$configDir)) {
            throw new \Exception("需要使用Config::getConfigDir()设置配置文件所在路径", 1);
        }
        self::$configFile[$key] = self::$configDir . '/' . $key . '.php';
        if (file_exists(self::$configFile[$key])) {
            return require self::$configFile[$key];
        }
        return false;
    }
}
