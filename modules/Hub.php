<?php
/**
 * 实例化中心
 * 
 */
namespace amber\modules;
class Hub
{
    /**
     * hub集合
     * @var array
     */
    public static $hub = array();

    /**
     * 实例集合
     * @var array
     */
    public static $instance = array();

    /**
     * 绑定某个类到hub
     * @param   $name  
     * @param   $class 
     * @return         
     */
    public static function bind(string $name, callable $closure)
    {
        self::$hub[$name] = $closure;
    }

    /**
     * 工厂模式
     * @param   $name
     * @return
     */
    public static function factory(string $name)
    {
        return call_user_func(self::$hub[$name]);
    }

    /**
     * 获取单例
     * @param  绑定的名称 $name
     * @return     
     */
    public static function singleton(string $name)
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
}