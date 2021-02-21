<?php
namespace amber\modules\DB;

use amber\modules\Hub;

/**
 * SQLite模块
 */
abstract class SQLite extends Base
{
    /**
     * 数据库连接
     */
    protected $Connection = null;

    /**
     * 表名称
     */
    public $tableName = null;

    /**
     * 记录最后一条statement错误信息
     * @var null
     */
    protected $statementErrorInfo = null;

    /**
     * 检查表是否存在
     */
    public function tableExists($table = null)
    {
        $table = $table ? $table : $this->getTableName();
        $sth = $this->Connection->query("SELECT `name` from sqlite_master where type='table' AND name='{$table}'");
        if ($sth) {
            $res = $sth->fetchAll();
            $this->statementErrorInfo = $sth->errorInfo();
            return $res;
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
        $sth = $this->Connection->query("SELECT `name` from sqlite_master where type='table'");
        if ($sth) {
            $tables = array();
            foreach($sth->fetchAll() as $table) {
                if (strpos($table['name'], 'sqlite') === false) {
                    $tables[] = $table;
                }
            }
            return $tables;
        } else {
            return array();
        }
    }

    /**
     * 创建表
     * @param  mixed $table 表名称，更新表的时候可以穿入，自定义表名称
     * @return boolean
     */
    public function createTable($table = null)
    {
        $table = $table ? $table : $this->getTableName();
        
        if ($this->tableExists($table)) {
            return true;
        }

        $primaryKey = $this->getPrimaryKey();
        $sql = "CREATE TABLE `{$table}`(";
        foreach ($this->getScheme() as $column => $type) {
            $type = preg_replace('/int\([0-9]*\)/i', 'INTEGER', $type);
            $sql .= "`$column` $type,";
        }
        // 存在主键则添加
        if ($primaryKey = $this->getPrimaryKey()) {
            $sql .= "PRIMARY KEY ($primaryKey)";
        }
        $sql = rtrim($sql, ',');
        $sql .= ")";
        
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
     * 异常处理
     * @return void
     */
    protected function exceptionHandle()
    {
        $errorInfo = $this->Connection->errorInfo();
        throw new \Exception($errorInfo[2], 1);
    }

    /**
     * 更改表
     * 当表的scheme改变时候，可以调用changeTable来将表更新到最新的状态
     * 同时旧表将会备份
     */
    public function updateTable()
    {
        $tableName = $this->getTableName();
        $newTable = 'new_' . $tableName;
        $this->createTable('new_' . $tableName);
        $page = 1;
        while ($lists = $this->lists($page, 1000)) {
            $this->beginTransaction();
            foreach ($lists as $row) {
                $this->insert($row, $newTable);
            }
            $this->commit();
            $page += 1;
        }
        $oldTable = 'old_' . $tableName;
        // 删除老的表，并且将现在的表重命名为老的表
        if ($this->tableExists($oldTable)) {
            $this->Connection->exec("DROP TABLE $oldTable");
        }
        $this->Connection->exec("ALTER TABLE " . $tableName . " RENAME TO $oldTable");
        $this->Connection->exec("ALTER TABLE {$newTable} RENAME TO " . $tableName);
        return true;
    }

    /**
     * 获取之前的数据表
     * 没有则返回false
     * @return
     */
    public function getOldTable()
    {
        $tableName = $this->getTableName();
        if ($this->tableExists('old_' . $tableName)) {
            return 'old_' . $tableName;
        } else {
            return false;
        }
    }

    /**
     * 删除表
     * @param  string $tableName 表名
     * @return boolean
     */
    public function deleteTable($tableName = '')
    {
        if (!$tableName) {
            $tableName = $this->getTableName();
        }
        $this->Connection->exec("DROP TABLE $tableName");
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
        $this->Connection->exec("VACUUM");
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
        return $this->getVars(['*'], $where, $bind);
    }

    /**
     * 获取单个字段
     */
    public function getVar($field, $where, $bind)
    {
        $data = $this->getVars([$field], $where, $bind);
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
    public function getVars(array $fields, $where, $bind)
    {
        $field = implode(',', $fields);
        $tableName = $this->getTableName();
        $prepare = "SELECT $field FROM `{$tableName}` WHERE {$where} LIMIT 1";
        $sth = $this->Connection->prepare($prepare);
        if ($sth) {
            $sth->execute($bind);
            $res = $sth->fetch();
            $this->statementErrorInfo = $sth->errorInfo();
            return $res;
        } else {
            return false;
        }
    }

    /**
     * 获取列表
     * @param  string $where 
     * @param  string $limit
     * @return array
     */
    public function gets($where, $bind, $limit = 10, $order = '', $fields = '*')
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
        $sth = $this->Connection->prepare($query);
        if ($sth) {
            $sth->execute($bind);
            $res = $sth->fetchAll();
            $this->statementErrorInfo = $sth->errorInfo();
            return $res;
        } else {
            return false;
        }
    }

    /**
     * 列表
     */
    public function lists($page=1, $perpage = 20, $orderby = null, $order = null)
    {
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
        if ($sth = $this->Connection->query($query)) {
            $res = $sth->fetchAll();
            $this->statementErrorInfo = $sth->errorInfo();
            return $res;
        } else {
            return false;
        }
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
     * 显示错误信息
     * @return array
     */
    public function errorInfo()
    {
        return $this->Connection->errorInfo();
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
     * 获取dbsize
     * @return
     */
    public function dbSize()
    {
        return 0;
        //return filesize($this->dbFile);
    }
}
