<?php
namespace amber\modules;

class Route
{

    /**
     * callback function
     * @var callable
     */
    protected static $callback;

    /**
     * match string or match regexp
     * @var string
     */
    protected static $match;

    protected static $routeFound = false;

    /**
     * args
     * @var mixed
     */
    protected static $args = null;

    /**
     * deal with get,post,head,put,delete,options,head
     * @param   $method
     * @param   $arguments
     * @return
     */
    public static function __callstatic($method, $arguments)
    {
        self::$match = stripslashes(str_replace("//", "/", dirname($_SERVER['PHP_SELF']) . $arguments[0]));
        self::$callback = $arguments[1];
        self::dispatch();
        return;
    }

    /**
     * processing ordinary route matches
     * @param  string $requestUri
     * @return
     */
    public static function normalMatch($requestUri)
    {
        if (self::$match == $requestUri) {
            self::$routeFound = true;
            call_user_func(self::$callback);
        }
        return;
    }

    /**
     * processing regular route matches
     * @param  string $requestUri
     * @return
     */
    public static function regexpMatch($requestUri)
    {
        $regexp = self::$match;
        preg_match("#$regexp#", $requestUri, $matches);
        if (!empty($matches)) {
            self::$routeFound = true;
            self::$args = $matches;
            call_user_func(self::$callback, $matches);
        }
        return;
    }

    /**
     * dispatch route
     * @return
     */
    public static function dispatch()
    {
        if (self::$routeFound) {
            return ;
        }
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        if (strpos(self::$match, '(') === false) {
            self::normalMatch($requestUri);
        } else {
            self::regexpMatch($requestUri);
        }

    }

    /**
     * Determining whether the route is found
     * @return boolean
     */
    public static function isNotFound()
    {
        return !self::$routeFound;
    }

    public static function getArgs()
    {
        return self::$args;
    }

}
