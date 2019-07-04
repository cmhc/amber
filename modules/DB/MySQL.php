<?php
/**
 * MySQL模块
 * 同一个配置会共用同一个连接
 */
namespace amber\modules\DB;
use amber\modules\Hub;

abstract class MySQL
{
    protected $sth = null;

    /**
     * 连接
     * @var resource
     */
    protected $Connection;

    /**
     * 配置
     * 需要在子类里面定义配置
     */
    protected $config = array();

    public function __construct()
    {
        if (!$this->scheme) {
            throw new \Exception("需要设定scheme", 1);
        }
        if (!$this->table) {
            throw new \Exception("需要设置table名称", 1);
        }
        if (!$this->config) {
            throw new \Exception("需要设置配置文件", 1);
        }

        $this->dbHash = md5(json_encode($this->config));
        Hub::bind($this->dbHash, function() {
            return $this->connect();
        });
        $this->Connection = Hub::singleton($this->dbHash);
    }

    /**
     * 初始化pdo
     * 连接失败会抛出异常，这里不捕获
     */
    protected function connect()
    {
        $port = isset($this->config['port']) ? $this->config['port'] : 3306;
        $dsn = "mysql:dbname=" . $this->config['dbname'] . ";host=" . $this->config['host']. ':' . $port;
        $connection = new \PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
        $connection->query("SET NAMES '{$charset}'");
        return $connection;
    }

    /**
     * 获取表名称
     * @return string
     */
    public function getTableName()
    {
        return $this->table;
    }

    /**
     * 创建表
     */
    public function createTable($table = null)
    {
        $table = $table ? $table : $this->table; // 更新表的时候传入
        
        if ($this->tableExists($table)) {
            return true;
        }

        $sql = "CREATE TABLE `{$table}`(";
        foreach ($this->scheme['fields'] as $column=>$type) {
            if ($column == 'id') {
                $sql .= "`$column` $type NOT NULL PRIMARY KEY AUTO_INCREMENT,";
            } else {
                $sql .= "`$column` $type,";
            }
        }
        $sql = rtrim($sql, ',');
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";

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
                $key = $key . time();
                $sql = "ALTER TABLE `{$table}` ADD {$type} (`{$field}`)";
                $this->Connection->exec($sql);
            }
        }

        return $this->tableExists($table);
    }

    /**
     * 检查表是否存在
     * @param  string $table 表名称
     */
    public function tableExists($table = null)
    {
        $table = !$table ? $this->table : $table;
        $sth = $this->Connection->query("show tables like '{$table}'");
        if ($sth) {
            $res = $sth->fetchAll();
            $this->statementErrorInfo = $sth->errorInfo();
            return $res[0][0] == $table;
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
     * 删除表
     * 该方法只会删除游离的表
     * @return boolean
     */
    public function deleteTable($table = null)
    {
        $table = $table ? $table : $this->table;
        $this->Connection->exec("DROP TABLE $table");
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

        $this->Connection->exec("optimize table {$this->table}");
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
        $prepare = "SELECT $field FROM `{$this->table}` WHERE {$where} LIMIT 1";
        $sth = $this->Connection->prepare($prepare);
        if ($sth) {
            $sth->execute($bind);
            $res = $sth->fetch(\PDO::FETCH_ASSOC);
            $this->statementErrorInfo = $sth->errorInfo();
            $sth->closeCursor();
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
    public function gets($where = '', $bind = array(), $limit = '10', $order = '', $fields = '*')
    {
        if (strpos($fields, ',') !== false) {
            $fields = explode(',', $fields);
            $fields = array_map(function($field){
                return trim($field);
            }, $fields);
            $fields = implode('`,`', $fields);
        }
        $query = "SELECT `{$fields}` FROM `{$this->table}`";
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
     * 联合查询
     * @param  array  $where
     * @param  array  $bind
     * @param  array  $limit
     * @param  array  $order
     * @return 
     */
    public function union(array $where, array $bind, array $limit = array(), array $order = array(), $fields = '*')
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
            $query .= "SELECT `{$fields}` FROM (SELECT `{$fields}` FROM `{$this->table}` WHERE {$where[$i]}";
            if ($order[$i]) {
                $query .= " ORDER BY {$order[$i]}";
            }
            if ($limit) {
                $query .= " LIMIT $limit[$i]";
            }
            $query .= ') as t UNION ALL ';
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
     * insert action
     * @param  string $table table name
     * @param  array  $data  data
     * @return boolean
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
            $res = $sth->execute($data);
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
    public function minsert($table, aray $data)
    {
        $fields = implode(",",$data['field']);
        $values = '(' . implode('),(',$data['data']) . ')';
        $sql = "INSERT INTO {$table}($fields) VALUES{$values}";
        return $this->Connection->exec($sql);
    }

    /**
     * 删除操作
     * @param  string $where
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
            $row = $sth->rowCount();
            return $row;
        } else {
            return false;
        }
    }

    /**
     * 删除表
     * @param  string $table
     * @return boolean
     */
    public function dropTable($table = null)
    {
        $table = $table ? $table : $this->table;
        if (!$this->tableExists($table)) {
            return true;
        }
        return $this->Connection->exec("DROP TABLE `{$table}`");
    }

   /**
     * 列表
     */
    public function lists($page=1, $perpage = 20, $orderby = 'id', $order = 'ASC')
    {
        $sublimit = ($page-1) * $perpage . ',1';
        $subquery = "SELECT `{$orderby}` FROM `{$this->table}` ORDER BY {$orderby} {$order} LIMIT $sublimit";
        $op = ($order == 'ASC') ? '>=' : '<=';
        $query = "SELECT * FROM `{$this->table}` WHERE {$orderby} {$op} ($subquery) ORDER BY {$orderby} {$order} LIMIT $perpage";
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
        if ($sth = $this->Connection->query("SELECT count(*) FROM `{$this->table}`")) {
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