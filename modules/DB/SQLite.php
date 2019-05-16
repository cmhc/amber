<?php
/**
 * SQLite模块
 */
namespace amber\modules\DB;

use amber\modules\Hub;

abstract class SQLite
{
    /**
     * 数据库文件
     * @var null
     */
    protected $dbFile = null;

    /**
     * 表结构
     * @var array
     */
    protected $scheme = array();

    /**
     * 数据库连接
     */
    protected $Connection = null;

    /**
     * 表名称
     */
    public $table = null;

    public function __construct()
    {
        if (!$this->scheme) {
            throw new \Exception("需要设定scheme", 1);
        }
        if (!$this->table) {
            throw new \Exception("需要设置table名称", 1);
        } 
        $dbHash = md5($this->dbFile);
        Hub::bind($dbHash, function(){
            return new \PDO('sqlite:' . $this->dbFile);
        });
        $this->Connection = Hub::singleton($dbHash);
    }

    /**
     * 检查表是否存在
     */
    public function tableExists($table = null)
    {
        $table = !$table ? $this->table : $table;
        $sth = $this->Connection->query("SELECT `name` from sqlite_master where type='table' AND name='{$table}'");
        if ($sth) {
            return $sth->fetchAll();
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
     */
    public function createTable($table = null)
    {
        $table = $table ? $table : $this->table; // 更新表的时候传入
        $sql = "CREATE TABLE `{$table}`(";
        foreach ($this->scheme['fields'] as $column=>$type) {
            if ($column == 'id') {
                $sql .= "`$column` $type NOT NULL PRIMARY KEY AUTOINCREMENT,";
            } else {
                $sql .= "`$column` $type,";
            }
        }
        $sql = rtrim($sql, ',');
        $sql .= ")";

        $this->Connection->exec($sql);

        // 创建索引
        if ($this->scheme['keys']) {
            foreach ($this->scheme['keys'] as $field => $type) {
                if (strpos($field, ',') !== false) {
                    $fields = explode(',', $field);
                    $field = implode("`,`", $fields);
                    $key = implode(mt_rand(0,99), $fields);
                } else {
                    $key = $field;
                }
                $sql = "CREATE {$type} `{$key}` ON `{$table}` (`{$field}`)";
                $this->Connection->exec($sql);
            }
        }

        return $this->tableExists();
    }

    /**
     * 更改表
     * 当表的scheme改变时候，可以调用changeTable来将表更新到最新的状态
     * 同时旧表将会备份
     */
    public function updateTable()
    {
        $newTable = 'new_' . $this->table;
        $this->createTable('new_' . $this->table);
        $page = 1;
        while ($lists = $this->lists($page, 1000)) {
            $this->beginTransaction();
            foreach ($lists as $row) {
                $this->insert($row, $newTable);
            }
            $this->commit();
            $page += 1;
        }
        $oldTable = 'old_' . $this->table;
        // 删除老的表，并且将现在的表重命名为老的表
        $this->Connection->exec("DROP TABLE $oldTable");
        $this->Connection->exec("ALTER TABLE {$this->table} RENAME TO $oldTable");
        $this->Connection->exec("ALTER TABLE {$newTable} RENAME TO $this->table");
        return true;
    }

    /**
     * 获取之前的数据表
     * 没有则返回false
     * @return
     */
    public function getOldTable()
    {
        if ($this->tableExists('old_' . $this->table)) {
            return 'old_' . $this->table;
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
        $table = !$table ? $this->table : $table;
        // 根据scheme重新包装数据
        $insertData = array();
        foreach ($this->scheme['fields'] as $key=>$type) {
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
            return $sth->execute($data);
        } else {
            return false;
        }
    }

    /**
     * 删除操作
     */
    public function delete($where)
    {
        return $this->Connection->exec("DELETE FROM {$this->table} WHERE {$where}");
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
        foreach ($this->scheme['fields'] as $key=>$type) {
            if ($key == 'id' && (!isset($data[$key]) || !$data[$key])) {
                continue;
            }
            if (!isset($data[$key])) {
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
        $sth = $this->Connection->prepare("UPDATE `{$this->table}` SET {$set} WHERE {$where}");
        if ($sth->execute($data)) {
            return $sth->rowCount();
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
        $prepare = "SELECT $field FROM `{$this->table}` WHERE {$where} LIMIT 1";
        $sth = $this->Connection->prepare($prepare);
        if ($sth) {
            $sth->execute($bind);
            return $sth->fetch();
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
    public function gets($where, $bind, $limit = 10, $order = '')
    {
        $query = "SELECT * FROM `{$this->table}` WHERE {$where}";
        if ($order) {
            $query .= " ORDER BY {$order}";
        }
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        $sth = $this->Connection->prepare($query);
        if ($sth) {
            $sth->execute($bind);
            return $sth->fetchAll();
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
            $query .= "SELECT * FROM (SELECT * FROM `{$this->table}` WHERE {$where[0]}";
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
            return $sth->fetchAll();
        } else {
            return false;
        }
    }

    /**
     * 列表
     */
    public function lists($page=1, $perpage = 20)
    {
        $limit = ($page-1)*$perpage . ',' . $perpage;
        $query = "SELECT * FROM `{$this->table}` LIMIT $limit";
        if ($sth = $this->Connection->query($query)) {
            return $sth->fetchAll(); 
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
        if ($sth = $this->Connection->query("SELECT count(*) FROM `{$this->table}`")) {
            $res = $sth->fetch();
            return $res[0];
        }
        return false;

    }

    /**
     * 显示错误信息
     * @return string
     */
    public function errorInfo()
    {
        return $this->Connection->errorInfo();
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
