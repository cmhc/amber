<?php
/**
 * mysql模块测试
 */
require_once dirname(__DIR__) . '/load.php';
require_once __DIR__ . '/tableForTest.php';

use amber\modules\DB\Connection;
use amber\modules\DB\RowIterator;


class mysqlTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $config = array(
            'dbname' => 'test',
            'host' => '127.0.0.1',
            'port' => '3306',
            'username' => 'root',
            'password' => 'root'
        );
        $Table = new tableForTest(Connection::mysql($config));
        $this->RowIterator = new RowIterator($Table);
    }

    public function testForeach()
    {
        foreach ($this->RowIterator as $row) {
            print_r($row);
        }
    }

}