<?php
/**
 * mysql模块测试
 */
require_once __DIR__ . '/load.php';

class testTable extends amber\modules\DB\MySQL
{
    protected $config = array(
        'dbname' => 'test',
        'host' => '127.0.0.1',
        'port' => '3306',
        'username' => 'root',
        'password' => 'huchao199326'
    );

    protected $table = 'test_table';

    protected $scheme = array(
        'fields' => array(
            'id' => 'int(11)',
            'key' => 'varchar(255) NOT NULL'
        ),
        'keys' => array(
            'key' => 'INDEX'
        )
    );
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