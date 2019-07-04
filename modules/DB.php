<?php
namespace amber\modules;

/**
 * 数据库操作类
 */
class DB
{

    protected $pdo = null;

    protected $sth = null;

    /**
     * 初始化配置
     */
    protected $config = array();

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 初始化pdo
     * 连接失败会抛出异常，这里不捕获
     */
    protected function initpdo()
    {
        if (!empty($this->pdo)) {
            return;
        }
        if (!empty($this->config['dsn'])) {
            $dsn = $this->config['dsn'];
        } else {
            $dsn = $this->config['driver'] . ":dbname=" . $this->config['dbname'] . ";host=" . $this->config['host'];
        }
        $this->pdo = new \PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
        $this->pdo->query("SET NAMES '{$charset}'");
        return true;
    }

    /**
     * 执行一个查询, 会捕获异常
     * 在cli环境下，断线将会重新连接
     */
    public function query($sql, $prepare = '', $style = \PDO::FETCH_ASSOC, $fetchType = 'fetchAll')
    {
        if (empty($this->pdo) && !$this->initpdo()) {
            return false;
        }

        if ($prepare != '' && !is_array($prepare)) {
            return false;
        }

        try {
            if ($prepare == '') {
                $sth = $this->pdo->query($sql);
            } else {
                $sth = $this->pdo->prepare($sql);
                $sth->execute($prepare);
            }
        } catch (\PDOException $e) {
            if ($e->errorInfo[2] == 'MySQL server has gone away') {
                //重连
                $this->pdo = null;
                $this->initpdo();
            }
            return false;
        }

        $result = $sth->$fetchType($style);

        //关闭游标下一次才能继续执行相同的查询
        if ($fetchType == 'fetch') {
            $sth->closeCursor();
        }

        $this->sth = $sth;
        return $result;
    }

    /**
     * 获取单个值
     */
    public function getVar($sql, $prepare = '')
    {
        $result = $this->query($sql, $prepare, \PDO::FETCH_NUM, 'fetch');
        if (isset($result[0])) {
            return $result[0];
        }
        return false;
    }

    /**
     * 获取一行值
     * @param $sql string sql statement
     * @param $prepare array
     */
    public function getRow($sql, $prepare = '')
    {
        $result = $this->query($sql, $prepare, \PDO::FETCH_ASSOC, 'fetch');
        return $result;
    }

    /**
     * 获取查询的所有结果
     * @param $sql string sql statement
     * @param $prepare array
     * @param $style fetch
     */
    public function getAll($sql, $prepare = '', $style =  \PDO::FETCH_ASSOC)
    {
        $result = $this->query($sql, $prepare, $style, 'fetchAll');
        return $result;
    }

    /**
     * execute sql statement
     * @param $sql
     * @return int
     */
    public function exec($sql)
    {
        if (empty($this->pdo) && !$this->initpdo()) {
            return false;
        }
        return $this->pdo->exec($sql);
    }

    /**
     * insert action
     * @param  string $table table name
     * @param  array  $data  data
     * @return boolean
     */
    public function insert($table, array $data)
    {
        if (empty($this->pdo)) {
            $this->initpdo();
        }
        $keys = array_keys($data);
        $fields = str_replace(":", "", '`' . implode('`, `', $keys) . '`');

        /* 支持带有占位符格式的key和不带有占位符格式的key */
        $placeholder = ":" . implode(",:", $keys);
        $placeholder = str_replace("::", ":", $placeholder);

        //拼装data数组
        foreach ($data as $f => $v) {
            if (strpos($f, ':') === false) {
                $newData[':' . $f] = $v;
            } else {
                $newData[$f] = $v;
            }
        }

        try {
            $sth = $this->pdo->prepare("INSERT INTO {$table}({$fields}) VALUES({$placeholder})");
        } catch (\PDOException $e) {
            if ($e->errorInfo[2] == 'MySQL server has gone away') {
                //重连
                $this->pdo = null;
                $this->initpdo();
            }
            return false;
        }

        return $sth->execute($newData);
    }

    /**
     * 同时插入多行数据
     * @param  array $data
     * $data = array('field'=>array("field1","field2"),"data"=>array("d1,d2","d1,d2"))
     */
    public function minsert($table, aray $data)
    {
        if (!isset($this->pdo)) {
            $this->initpdo();
        }        
        $fields = implode(",",$data['field']);
        $values = '(' . implode('),(',$data['data']) . ')';
        $sql = "INSERT INTO {$table}($fields) VALUES{$values}";
        return $this->pdo->exec($sql);
    }

    /**
     * 更新数据
     * @param  string $table
     * @param  array $data
     * @param  string $where
     * @return boolean
     */
    public function update($table, $data, $where)
    {
        /* 对whwere进行测试,不允许没有条件的更新 */
        $whereArray = explode("=", $where);
        if (count($whereArray) < 2 || $whereArray[1] == '') {
            throw new \Exception("the query is not allowd", 1);
        }

        if (empty($this->pdo)) {
            $this->initpdo();
        }
        $set = '';
        //拼装set，组装更新数组
        foreach ($data as $f => $v) {
            $set .= " `{$f}`=:{$f},";
            if (strpos($f, ':') === false) {
                $newData[':' . $f] = $v;
            } else {
                $newData[$f] = $v;
            }
        }
        $set = rtrim($set, ',');

        try {
            $sth = $this->pdo->prepare("UPDATE `{$table}` SET {$set} WHERE {$where}");
        } catch (\PDOException $e) {
            if ($e->errorInfo[2] == 'MySQL server has gone away') {
                //重连
                $this->pdo = null;
                $this->initpdo();
            }
            return false;
        }

        return $sth->execute($newData);
    }

    /**
     * 创建表
     * id会自动设定为主键
     */
    public function createTable($table, $data, $key = null)
    {
        $sql = "CREATE TABLE `{$table}`(";
        foreach($data as $column => $type){
            if ($column == 'id') {
                $sql .= "`$column` $type UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,";
            }else{
                $sql .= "`$column` $type,";
            }
        }
        if ($key) {
            foreach ($key as $field => $type) {
                if (strpos($field, ',')) {
                    $field = str_replace(',', '`,`', $field);
                }
                $sql .= "{$type} (`{$field}`),";
            }
        }

        $sql = rtrim($sql, ',');
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
        return $this->exec($sql);
    }

    /**
     * 删除表
     * @param  string $table
     * @return boolean
     */
    public function dropTable($table)
    {
        return $this->exec("DROP table `{$table}`");
    }

    /**
     * 判断表是否存在
     * @param  string $table
     * @return boolean
     */
    public function existsTable($table)
    {
        $tables = $this->getAll("SHOW TABLES");
        foreach ($tables as $t) {
            if ($t[0] == $table) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取最后一个错误信息
     */
    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }

    /**
     * 获取插入id
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * get debug params
     */
    public function debugDumpParams()
    {
        return $this->sth->debugDumpParams();
    }

}
