<?php

/**
 * mysql 模块
 * @Author: huchao06
 * @Date:   2019-08-31 10:54:20
 * @Last Modified by:   huchao06
 * @Last Modified time: 2019-09-28 16:20:20
 */

namespace amber\modules\DB;

abstract class MySQL extends Base
{
    /**
     * 创建表
     * @param  string $table
     * @return boolean
     */
    public function createTable($table = null)
    {
        $table = $table ? $table : $this->getTableName(); // 更新表的时候传入
        
        if ($this->tableExists($table)) {
            return true;
        }

        $sql = "CREATE TABLE `{$table}`(";
        foreach ($this->getScheme() as $column=>$type) {
            $sql .= "`$column` $type,";
        }
        // 存在主键则添加
        if ($primaryKey = $this->getPrimaryKey()) {
            $sql .= "PRIMARY KEY ($primaryKey)";
        }
        $sql = rtrim($sql, ',');
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";

        $this->Connection->beginTransaction();

        if (false === $this->Connection->exec($sql)) {
            $this->Connection->rollBack();
            $this->exceptionHandle();
        }

        // 创建普通索引
        if ($keys = $this->getKeys()) {
            foreach ($keys as $field) {
                if (strpos($field, ',') !== false) {
                    $fields = explode(',', $field);
                    $field = implode("`,`", $fields);
                    $key = implode(mt_rand(0,99), $fields);
                } else {
                    $key = $field;
                }
                $key = $key . time();
                $sql = "CREATE INDEX `{$key}` ON `{$table}` (`{$field}`)";
                if (false === $this->Connection->exec($sql)) {
                    $this->Connection->rollBack();
                    $this->exceptionHandle();
                }
            }
        }

        //创建唯一值索引
        if ($keys = $this->getUniqueKeys()) {
            foreach ($keys as $field) {
                if (strpos($field, ',') !== false) {
                    $fields = explode(',', $field);
                    $field = implode("`,`", $fields);
                    $key = implode(mt_rand(0,99), $fields);
                } else {
                    $key = $field;
                }
                $key = $key . time();
                $sql = "CREATE UNIQUE INDEX `{$key}` ON `{$table}` (`{$field}`)";
                if (false === $this->Connection->exec($sql)) {
                    $this->Connection->rollBack();
                    $this->exceptionHandle();
                }
            }
        }

        $this->Connection->commit();
        return true;
    }

    /**
     * 检查表是否存在
     * @param  string $table 表名称
     * @return  boolean
     */
    public function tableExists($table = null)
    {
        $table = !$table ? $this->getTableName() : $table;
        $sth = $this->Connection->query("show tables like '{$table}'");
        if ($sth) {
            $res = $sth->fetchAll();
            $this->statementErrorInfo = $sth->errorInfo();
            return isset($res[0][0]) && ($res[0][0] == $table);
        } else {
            return false;
        }
    }

    /**
     * 获取所有的表
     * @return array
     */
    public function getTables()
    {
        $sth = $this->Connection->query("show tables");
        if ($sth) {
            $tables = array();
            foreach($sth->fetchAll() as $table) {
                $tables[] = $table;
            }
            return $tables;
        } else {
            return array();
        }
    }

    /**
     * 更改表
     * 当表的scheme改变时候，可以调用changeTable来将表更新到最新的状态
     * 同时旧表将会备份
     */
    public function updateTable()
    {
        $newTable = 'new_' . $this->getTableName();
        $this->createTable('new_' . $this->getTableName());
        $page = 1;
        while ($lists = $this->lists($page, 1000)) {
            $this->beginTransaction();
            foreach ($lists as $row) {
                $this->insert($row, $newTable);
            }
            $this->commit();
            $page += 1;
        }
        $oldTable = 'old_' . $this->getTableName();
        $tableName = $this->getTableName();
        // 删除老的表，并且将现在的表重命名为老的表
        $this->Connection->exec("DROP TABLE $oldTable");
        $this->Connection->exec("ALTER TABLE {$tableName} RENAME TO $oldTable");
        $this->Connection->exec("ALTER TABLE {$newTable} RENAME TO $tableName");
        return true;
    }
    
    /**
     * 清空表
     * @param  string $table
     */
    public function truncate()
    {
        $table = $this->getTableName();
        $this->Connection->exec("TRUNCATE TABLE $table");
        $error = $this->Connection->errorInfo();
        if ($error[0] == '00000') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 优化表
     * @return boolean
     */
    public function optimize()
    {
        $tableName = $this->getTableName();
        $this->Connection->exec("optimize table {$tableName}");
        $error = $this->Connection->errorInfo();
        if ($error[0] == '00000') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 开始一个事务
     * @return
     */
    public function beginTransaction()
    {
        return $this->Connection->beginTransaction();
    }

    /**
     * 提交事务
     * @return
     */
    public function commit()
    {
        return $this->Connection->commit();
    }

    /**
     * 回滚事务
     * @return
     */
    public function rollBack()
    {
        return $this->Connection->rollBack();
    }

   /**
     * 查询单行数据
     * @param  string $where
     * @param  array $bind
     */
    public function get($where, $bind)
    {
        return $this->getVars('*', $where, $bind);
    }

    /**
     * 获取单个字段
     */
    public function getVar($field, $where, $bind)
    {
        $data = $this->getVars($field, $where, $bind);
        if ($data) {
            return $data[$field];
        } else {
            return false;
        }
    }
    /**
     * 获取多个field字段
     * @param  array $fields
     * @param  string $where 条件
     * @param  array $bind 绑定字段
     * @return array | boolean
     */
    public function getVars($fields, $where, $bind, $order = null)
    {
        if (is_array($fields)) {
            $fields =  '`' . implode('`,`', $fields) . '`';
        }
        $result = $this->select($fields, $where, $bind, $order, 1);
        return isset($result[0]) ? $result[0] : false;
    }

    /**
     * 获取列表
     * @param  string $where 
     * @param  string $limit
     * @return array
     */
    public function gets($where = '', $bind = array(), $limit = '10', $order = '', $fields = '*')
    {
        $tableName = $this->getTableName();
        if ($fields != '*' && strpos($fields, ',') !== false) {
            $fields = explode(',', $fields);
            $fields = array_map(function($field){
                return trim($field);
            }, $fields);
            $fields = '`' . implode('`,`', $fields) . '`';
        }
        $query = "SELECT {$fields} FROM `{$tableName}`";
        if ($where) {
            $query .= " WHERE {$where}";
        }
        if ($order) {
            $query .= " ORDER BY {$order}";
        }
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        // echo $query;
        $sth = $this->Connection->prepare($query);
        if ($sth) {
            $sth->execute($bind);
            $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
            $this->statementErrorInfo = $sth->errorInfo();
            return $res;
        } else {
            return false;
        }
    }

    /**
     * 同时插入多行数据
     * @param  array $data
     * $data = array('field'=>array("field1","field2"),"data"=>array("d1,d2","d1,d2"))
     */
    public function minsert($table, array $data)
    {
        $fields = implode(",",$data['field']);
        $values = '(' . implode('),(',$data['data']) . ')';
        $sql = "INSERT INTO {$table}($fields) VALUES{$values}";
        return $this->Connection->exec($sql);
    }

    /**
     * 删除表
     * @param  string $table
     * @return boolean
     */
    public function dropTable($table = null)
    {
        $table = $table ? $table : $this->getTableName();
        if (!$this->tableExists($table)) {
            return true;
        }
        return $this->Connection->exec("DROP TABLE `{$table}`");
    }

    /**
     * 列出数据库里面的行
     * @param  integer $page    
     * @param  integer $perpage 
     * @param  string  $orderby 
     * @param  string  $order   
     * @return array
     */
    public function lists($page=1, $perpage = 20, $orderby = null, $order = null)
    {
        $page = ($page < 1) ? 1 : $page;
        $sublimit = ($page-1) * $perpage . ',1';
        if (!$orderby) {
            if (method_exists($this, 'getSortKey')) {
                $orderby = $this->getSortKey();
            } else if (method_exists($this, 'getPrimaryKey')) {
                $orderby = $this->getPrimaryKey();
            }
        }
        if (!$orderby) {
            throw new \Exception("排序key不存在", 1);
        }
        $order = $order ? $order : 'ASC';
        $tableName = $this->getTableName();
        $subquery = "SELECT `{$orderby}` FROM `{$tableName}` ORDER BY {$orderby} {$order} LIMIT $sublimit";
        $op = ($order == 'ASC') ? '>=' : '<=';
        $query = "SELECT * FROM `{$tableName}` WHERE {$orderby} {$op} ($subquery) ORDER BY {$orderby} {$order} LIMIT $perpage";
        $sth = $this->Connection->query($query);
        $res = $sth->fetchAll();
        $this->errorHandle($sth);
        return $res;
    }

    /**
     * 获取表行数量
     * @return int
     */
    public function count()
    {
        $tableName = $this->getTableName();
        if ($sth = $this->Connection->query("SELECT count(*) FROM `{$tableName}`")) {
            $res = $sth->fetch();
            $this->statementErrorInfo = $sth->errorInfo();
            return $res[0];
        }
        return false;
    }

    /**
     * 获取最后一个错误信息
     */
    public function errorInfo()
    {
        return $this->Connection->errorInfo();
    }

    /**
     * 获取插入id
     */
    public function lastInsertId()
    {
        return $this->Connection->lastInsertId();
    }

    /**
     * 获取statement error info
     * @return array
     */
    public function statementErrorInfo()
    {
        return $this->statementErrorInfo;
    }


    /**
     * get debug params
     */
    public function debugDumpParams()
    {
        return $this->sth->debugDumpParams();
    }
}