<?php
/**
 * mysql模块测试
 */
require_once dirname(__DIR__) . '/load.php';

class tableForTest extends amber\modules\DB\MySQL
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

}