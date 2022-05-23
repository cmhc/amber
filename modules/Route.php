<?php
namespace amber\modules;

class Route
{

    /**
     * 回调函数
     * @var callable
     */
    protected static $callback;

    /**
     * 匹配的正则
     * @var string
     */
    protected static $match;

    /**
     * 表示当前是否有路由被发现
     * @var boolean
     */
    protected static $routeFounded = false;

    /**
     * args
     * @var mixed
     */
    protected static $args = null;

    /**
     * 限制
     * @var 
     */
    protected static $limit;

    /**
     * 当前请求的uri
     * @var string
     */
    protected static $requestUri;

    /**
     * deal with get,post,head,put,delete,options,head
     * @param   $method
     * @param   $arguments
     * @return
     */
    public static function __callstatic($method, $arguments)
    {
        self::$match = $arguments[0];
        self::$callback = $arguments[1];
        self::$limit = isset($arguments[2]) ? $arguments[2] : array();
        self::dispatch();
        return;
    }

    /**
     * 匹配过程
     * @param  string $requestUri
     * @return
     */
    public static function match()
    {
        $exp = array();
        $keys = array();
        foreach (explode('/', self::$match) as $part) {
            if (false !== $start = strpos($part, '{')) {
                $end = strpos($part, '}', $start);
                $key = substr($part, $start+1, $end-$start-1);
                $keys[] = $key;
                if (isset(self::$limit[$key])) {
                    $exp[] = str_replace('{'.$key.'}', '('.self::$limit[$key].')', $part);
                } else {
                    $exp[] = str_replace('{'.$key.'}', '(.*?)', $part);
                }
            } else {
                $exp[] = $part;
            }
        }
        $regexp = implode('/', $exp);
        preg_match("#^{$regexp}$#", self::$requestUri, $matches);
        $args = array();
        if (!empty($matches)) {
            self::$routeFounded = true;
            foreach ($keys as $id=>$key) {
                $args[$key] = $matches[$id+1];
            }
            self::$args = $args;
            call_user_func_array(self::$callback, $args);
        }
        return;
    }

    /**
     * dispatch route
     * @return
     */
    public static function dispatch()
    {
        if (self::$routeFounded) {
            return ;
        }
        self::$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // 去掉子目录
        if ('/' != $dir = dirname($_SERVER['PHP_SELF'])) {
            self::$requestUri = str_replace($requestUri, $dir, '/');
        }
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        self::match();
    }

    /**
     * 判断是否找到一条路由
     * @return boolean
     */
    public static function isNotFound()
    {
        return !self::$routeFounded;
    }

    /**
     * 获取匹配到的参数
     * @return array
     */
    public static function getArgs()
    {
        return self::$args;
    }

    /**
     * 获取当前页面的url
     * @return  string 当前页面的地址
     */
    public static function getURL()
    {
        if (!self::$routeFounded) {
            return false;
        }
        $home = (($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim( stripslashes(dirname($_SERVER['PHP_SELF'])), '/');
        return $home . self::$requestUri;
    }
}
