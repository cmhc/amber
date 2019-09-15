<?php
/**
 * mysql模块测试
 */
require_once __DIR__ . '/load.php';

class testTable extends amber\modules\DB\Base
{
   /**
     * 子类需要实现获取表名称的方法
     * @return string
     */
    public function getTableName()
    {
        return 'test_table';
    }

    /**
     * 子类需要实现的get scheme方法，用于建表
     * @return array
     */
    public function getScheme()
    {
        return array(
            'fields' => array(
                'id' => 'int(11)',
                'key' => 'varchar(255) NOT NULL',
                'value' => 'varchar(255) NOT NULL'
            )
        );
    }

    /**
     * 子类需要实现获取普通索引的方法
     * @return array
     */
    public function getKeys()
    {
        return array('value');
    }

    /**
     * 子类需要实现的获取主键的方法
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'id';
    }

    /**
     * 子类需要实现的获取唯一键的方法
     * @return array()
     */
    public function getUniqueKeys()
    {
        return array('key');
    }

    /**
     * 获取连接配置
     * @return array
     */
    public function getConfig()
    {
        return array(
            'dbname' => 'test',
            'host' => '127.0.0.1',
            'port' => '3306',
            'username' => 'root',
            'password' => 'huchao199326'
        );
    }

}

class mysqlTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $this->Table = new testTable();
        $this->Table->dropTable();
    }

    /**
     * 测试创建表
     */
    public function testCreateTable()
    {
        $this->assertTrue($this->Table->createTable());
    }

    /**
     * 测试表存在
     * @return 
     */
    public function testTableExists()
    {
        $this->assertTrue($this->Table->tableExists());
    }

}