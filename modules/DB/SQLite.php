<?php
namespace amber\modules\DB;

use amber\modules\Hub;

/**
 * SQLite模块
 */
abstract class SQLite
{
    /**
     * 数据库文件
     * @var null
     */
    protected $dbFile = null;

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
     * 子类需要实现获取表名称的方法
     * @return string
     */
    abstract public function getTableName();

    /**
     * 子类需要实现的get scheme方法，用于建表
     * @return array
     */
    abstract public function getScheme();

    /**
     * 子类需要实现的排序字段的方法
     * @return stirng
     */
    abstract public function getSortKey();

    /**
     * 子类需要实现获取普通索引的方法
     * @return array
     */
    abstract public function getKeys();

    /**
     * 子类需要实现的获取主键的方法
     * @return string
     */
    abstract public function getPrimaryKey();

    /**
     * 子类需要实现的获取唯一键的方法
     * @return array()
     */
    abstract public function getUniqueKeys();


    public function __construct()
    {
        $this->tableName = $this->getTableName();
        $dbHash = md5($this->dbFile);
        Hub::bind($dbHash, function() {
            return new \PDO('sqlite:' . $this->dbFile);
        });
        $this->Connection = Hub::singleton($dbHash);
    }

    /**
     * 检查表是否存在
     */
    public function tableExists($table = null)
    {
        $table = $table ? $table : $this->tableName;
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
        $table = $table ? $table : $this->tableName;
        
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
                if (fasle === $this->Connection->exec($sql)) {
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
        $newTable = 'new_' . $this->tableName;
        $this->createTable('new_' . $this->tableName);
        $page = 1;
        while ($lists = $this->lists($page, 1000)) {
            $this->beginTransaction();
            foreach ($lists as $row) {
                $this->insert($row, $newTable);
            }
            $this->commit();
            $page += 1;
        }
        $oldTable = 'old_' . $this->tableName;
        // 删除老的表，并且将现在的表重命名为老的表
        $this->Connection->exec("DROP TABLE $oldTable");
        $this->Connection->exec("ALTER TABLE " . $this->tableName . " RENAME TO $oldTable");
        $this->Connection->exec("ALTER TABLE {$newTable} RENAME TO " . $this->tableName);
        return true;
    }

    /**
     * 获取之前的数据表
     * 没有则返回false
     * @return
     */
    public function getOldTable()
    {
        if ($this->tableExists('old_' . $this->tableName)) {
            return 'old_' . $this->tableName;
        } else {
            return false;
        }
    }

    /**
     * 删除表
     * 该方法只会删除游离的表
     * @return boolean
     */
    public function deleteTable($tableName)
    {
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
     * 插入操作
     */
    public function insert($data, $table = null)
    {
        $table = $table ? $table : $this->tableName;
        // 根据scheme重新包装数据
        $insertData = array();
        foreach ($this->getScheme() as $key=>$type) {
            if ($key == 'id' && empty($data[$key])) {
                continue;
            }
            $value = isset($data[$key]) ? $data[$key] : '';
            $insertData[$key] = $value;
        }
        $keys = array_keys($insertData);
        $fields = str_replace(":", "", '`' . implode('`, `', $keys) . '`');

        //支持带有占位符格式的key和不带有占位符格式的key
        $placeholder = ":" . implode(",:", $keys);
        $placeholder = str_replace("::", ":", $placeholder);

        //拼装data数组
        $data = array();
        foreach ($insertData as $f => $v) {
            if (strpos($f, ':') === false) {
                $data[':' . $f] = $v;
            } else {
                $data[$f] = $v;
            }
        }
        $sth = $this->Connection->prepare("INSERT INTO {$table}({$fields}) VALUES({$placeholder})");
        if ($sth) {
            $res = $sth->execute($data);
            $this->statementErrorInfo = $sth->errorInfo();
            return $res;
        } else {
            return false;
        }
    }

    /**
     * 删除操作
     */
    public function delete($where)
    {
        return $this->Connection->exec("DELETE FROM {$this->tableName} WHERE {$where}");
    }

    /**
     * 更新操作
     * @param  array $data
     * @param  string $where
     * @return  int 影响的行数
     */
    public function update($data, $where)
    {
        // 对whwere进行测试,不允许没有条件的更新
        $whereArray = explode("=", $where);
        if (count($whereArray) < 2 || $whereArray[1] == '') {
            throw new \Exception("where 条件不允许为空", 1);
        }
        $set = '';
        // 根据scheme重新包装数据
        $updateData = array();
        foreach ($this->getScheme() as $key=>$type) {
            if (!isset($data[$key])) {
                continue;
            }
            // 主键不更新
            if ($key == $this->getPrimaryKey()) {
                continue;
            }
            $updateData[$key] = $data[$key];
        }
        //拼装set，组装更新数组
        $data = array();
        foreach ($updateData as $f => $v) {
            $set .= " `{$f}`=:{$f},";
            if (strpos($f, ':') === false) {
                $data[':' . $f] = $v;
            } else {
                $data[$f] = $v;
            }
        }
        $set = rtrim($set, ',');
        $sth = $this->Connection->prepare("UPDATE `{$this->tableName}` SET {$set} WHERE {$where}");
        if ($sth->execute($data)) {
            $row = $sth->rowCount();
            return $row;
        } else {
            return false;
        }
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
        $prepare = "SELECT $field FROM `{$this->tableName}` WHERE {$where} LIMIT 1";
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
        if ($fields != '*' && strpos($fields, ',') !== false) {
            $fields = explode(',', $fields);
            $fields = array_map(function($field){
                return trim($field);
            }, $fields);
            $fields = '`' . implode('`,`', $fields) . '`';
        }
        $query = "SELECT {$fields} FROM `{$this->tableName}`";
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
     * 联合查询
     * @param  array  $sql
     * @param  array  $bind
     * @param  array  $limit
     * @return 
     */
    public function union(array $where, array $bind, array $limit = array(), array $order = array())
    {
        $count = count($where);
        // 所有的必须相等
        if ($count !== count($bind)) {
            return false;
        }
        if ($count !== count($bind) || 
            ($limit && $count !== count($limit)) ||
            ($order && $count !== count($order))
        ) {
            return false;
        } 
        //union开始
        $query = '';
        for ($i=0; $i<$count; $i++) {
            $query .= "SELECT * FROM (SELECT * FROM `{$this->tableName}` WHERE {$where[0]}";
            if ($limit) {
                $query .= " LIMIT $limit[0]";
            }
            if ($order) {
                $query .= " ORDER BY {$order}";
            }
            $query .= ') UNION ALL ';
        }
        $query = rtrim($query, 'UNION ALL ');
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
        $orderby = $orderby ? $orderby : $this->getSortKey();
        $order = $order ? $order : 'ASC';
        $subquery = "SELECT `{$orderby}` FROM `{$this->tableName}` ORDER BY {$orderby} {$order} LIMIT $sublimit";
        $op = ($order == 'ASC') ? '>=' : '<=';
        $query = "SELECT * FROM `{$this->tableName}` WHERE {$orderby} {$op} ($subquery) ORDER BY {$orderby} {$order} LIMIT $perpage";
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
        if ($sth = $this->Connection->query("SELECT count(*) FROM `{$this->tableName}`")) {
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
        return filesize($this->dbFile);
    }
}
