<?php
/**
 * 实例中心
 */
namespace amber\modules;

class Hub
{
    /**
     * hub集合
     * @var array
     */
    protected static $hub = array();

    /**
     * 实例集合
     * @var array
     */
    protected static $instance = array();

    /**
     * 绑定某个类到hub
     * @param   $name 别名
     * @param   $closure 一个可执行闭包
     * @return
     */
    public static function bind($name, $closure)
    {
        self::$hub[$name] = $closure;
    }

    /**
     * 工厂模式
     * @param   $name
     * @return
     */
    public static function factory($name)
    {
        return call_user_func(self::$hub[$name]);
    }

    /**
     * 获取单例
     * @param  绑定的名称 $name
     * @return
     */
    public static function singleton($name)
    {
        if (isset(self::$instance[$name])) {
            return self::$instance[$name];
        }

        if (!isset(self::$hub[$name])) {
            if (class_exists($name)) {
                self::$instance[$name] = new $name;
            } else {
                self::$instance[$name] = false;
            }
        } else {
            self::$instance[$name] = call_user_func(self::$hub[$name]);
        }

        return self::$instance[$name];
    }

    /**
     * 释放singleton对象
     * @param  string $name
     * @return
     */
    public static function destory($name)
    {
        unset(self::$instance[$name]);
    }

    /**
     * 检查别名是否已经被绑定
     * @param  string $name
     * @return boolean
     */
    public static function exists($name)
    {
        return isset(self::$hub[$name]);
    }
}