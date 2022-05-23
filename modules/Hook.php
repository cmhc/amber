<?php
namespace amber\modules;

class Hook
{
    public static $hooks = array();

    /**
     * add hook
     * @param string   $name     hook name
     * @param callable $callback callback function
     * @param integer  $priority priority
     * @param integer  $args     number of accepted parameters
     */
    public static function add($name, callable $callback, $priority = 10, $acceptArgs = 1)
    {
        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = array();
        }
        if (!isset(self::$hooks[$name][$priority])) {
            self::$hooks[$name][$priority] = array();
        }
        $key = self::buildKey($callback);
        self::$hooks[$name][$priority][$key] = array(
            'function' => $callback,
            'accept_args' => $acceptArgs
        );
        if ($priority != 10 && count(self::$hooks[$name]) > 1) {
            ksort(self::$hooks[$name], SORT_NUMERIC);
        }
    }

    /**
     * apply hook
     * @param  string $name
     * @return boolean
     */
    public static function apply($name, $args = array())
    {
        if (!isset(self::$hooks[$name])) {
            if (isset($args[0])) {
                return $args[0];
            }
            return true;
        }
        $argsCount = count($args);
        foreach(self::$hooks[$name] as $priority=>$hooks) {
            foreach($hooks as $hook) {
                if ($argsCount == 0) {
                    $value = call_user_func_array($hook['function'], array());
                } else if($hook['accept_args'] >= $argsCount) {
                    $value = call_user_func_array($hook['function'], $args);
                    $args[0] = $value;
                } else {
                    $value = call_user_func_array($hook['function'], array_slice($args, 0, $hook['accept_args']));
                    $args[0] = $value;
                }
            }
        }
        return $value;
    }

    /**
     * remove callback function
     * @param  string $name
     * @param  callable $callback
     * @return boolean
     */
    public static function remove($name, $callback, $priority = 10)
    {
        $key = self::buildKey($callback);
        if (isset(self::$hooks[$name][$priority][$key])) {
            unset(self::$hooks[$name][$priority][$key]);
            return true;
        }
        return false;
    }

    /**
     * build key
     */
    public static function buildKey($callback)
    {
        if (is_string($callback)) {
            return $callback;
        }
        if (is_object($callback)) {
            return  spl_object_hash($callback);
        }
        if (is_string($callback[0])) {
            return $callback[0] . '::' . $callback[1];
        }
        if (is_object($callback[0])) {
            return spl_object_hash($callback[0]) . $callback[1];
        }
    }

    /**
     * exists
     * @param  string $name 
     * @return boolean
     */
    public static function exists($name)
    {
        return isset(self::$hooks[$name]);
    }
}