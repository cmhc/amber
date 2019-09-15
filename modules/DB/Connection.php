<?php

/**
 * 连接管理
 * @Author: huchao06
 * @Date:   2019-09-01 22:08:41
 * @Last Modified by:   huchao06
 * @Last Modified time: 2019-09-01 23:26:44
 */
namespace amber\modules\DB;

class Connection
{
    /**
     * mysql连接
     * @param  array $config
     * @return object
     */
    public static function mysql($config)
    {
        $port = isset($config['port']) ? $config['port'] : 3306;
        $charset = isset($config['charset']) ? $config['charset'] : 'utf8';
        $options = isset($config['options']) ? $config['options'] : array();
        $options = array_merge($options, array(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION));
        $dsn = sprintf("mysql:host=%s:%d;dbname=%s;charset=%s",
                $config['host'],
                $port,
                $config['dbname'],
                $charset);
        return new \PDO($dsn, $config['username'], $config['password'], $options);
    }

    /**
     * dblite连接
     * @param  string $file 数据库文件
     * @return object
     */
    public static function dblite($file)
    {
        return new \PDO('sqlite:' . $file);
    }
}
