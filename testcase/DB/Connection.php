<?php

/**
 * 连接测试
 * @Author: huchao06
 * @Date:   2019-09-01 22:40:06
 * @Last Modified by:   huchao06
 * @Last Modified time: 2019-10-02 16:20:40
 */
require_once dirname(__DIR__) . '/load.php';
use amber\modules\DB\Connection;
class ConnectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * 测试创建表
     */
    public function testMysqlConnection()
    {
        $config = array(
            'dbname' => 'test',
            'host' => '127.0.0.1',
            'port' => '3306',
            'username' => 'root',
            'password' => 'root'
        );
        $Connection = Connection::mysql($config);
        $actual = ($Connection instanceof \PDO);
        $this->assertTrue($actual);
    }

}