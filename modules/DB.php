<?php
namespace amber\modules;

/**
 * 简单数据库操作类，基于pdo
 * update 14:35 2015/3/13  查询出错直接返回false，不再有fetch操作
 */
class DB
{

    private $_pdo = null;

    private $_sth = null;

    /**
     * db config
     * array
     */
    private $config;

    private static $handle = array();

    private function __construct()
    {}

    public static function getInstance($config)
    {
        $hash = md5(json_encode($config));
        if (!isset(self::$handle[$hash])) {
            self::$handle[$hash] = new self;
            self::$handle[$hash]->setConfig($config);
        }
        return self::$handle[$hash];
    }

    private function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * create pdo handle
     * @return object
     */
    private function _initpdo()
    {
        if (isset($this->_pdo)) {
            return;
        }
        $dsn = $this->config['driver'] . ":dbname=" . $this->config['dbname'] . ";host=" . $this->config['host'];
        try {
            $this->_pdo = new \PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        $charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
        $this->_pdo->query("SET NAMES '{$charset}'");

    }

    /**
     * query sql
     */
    public function query($sql, $prepare = '', $style = \PDO::FETCH_ASSOC, $fetch_type = 'fetchAll')
    {

        if (!isset($this->_pdo)) {
            $this->_initpdo();
        }

        if ($prepare != '' && !is_array($prepare)) {
            return false;
        }

        if ($prepare == '') {
            $sth = $this->_pdo->query($sql);
        } else {
            $sth = $this->_pdo->prepare($sql);
            $sth->execute($prepare);
        }
        if (!$sth) {
            return false;
        }

        if ($style != '') {
            $result = $sth->$fetch_type($style);
        } else {
            $result = $sth->$fetch_type();
        }

        if ($fetch_type == 'fetch') {
            $sth->closeCursor();
        }

        $this->_sth = $sth;
        return $result;
    }

    /**
     * get single data
     */
    public function getVar($sql, $prepare = '')
    {
        $result = $this->query($sql, $prepare, \PDO::FETCH_NUM, 'fetch');
        return $result[0];
    }

    /**
     * get a row data
     * @param $sql string sql statement
     * @param $prepare array
     */
    public function getRow($sql, $prepare = '')
    {
        $result = $this->query($sql, $prepare, \PDO::FETCH_ASSOC, 'fetch');
        return $result;
    }

    /**
     * get all data which found
     * @param $sql string sql statement
     * @param $prepare array
     * @param $style fetch
     */
    public function getAll($sql, $prepare = '', $style = '')
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
        return $this->_pdo->exec($sql);
    }

    /**
     * insert action
     * @param  string $table table name
     * @param  array  $data  data
     * @return boolean
     */
    public function insert($table, array $data)
    {
        if (!isset($this->_pdo)) {
            $this->_initpdo();
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
        $sth = $this->_pdo->prepare("INSERT INTO {$table}({$fields}) VALUES({$placeholder})");
        if ($sth) {
            return $sth->execute($newData);
        } else {
            return false;
        }
    }

    /**
     * multi insert
     * @param  array $data
     * $data = array('field'=>array("field1","field2"),"data"=>array("d1,d2","d1,d2"))
     */
    public function minsert($table, $data)
    {
        if (!isset($this->_pdo)) {
            $this->_initpdo();
        }        
        $fields = implode(",",$data['field']);
        $values = '(' . implode('),(',$data['data']) . ')';
        $sql = "INSERT INTO {$table}($fields) VALUES{$values}";
        return $this->_pdo->exec($sql);
    }

    /**
     * update data
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

        if (!isset($this->_pdo)) {
            $this->_initpdo();
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

        $sth = $this->_pdo->prepare("UPDATE `{$table}` SET {$set} WHERE {$where}");
        if ($sth) {
            return $sth->execute($newData);
        } else {
            return false;
        }
    }

    /**
     * create table
     * id is always primary key
     */
    public function createTable($table, $data, $key = null)
    {
        $sql = "CREATE TABLE `{$table}`(";
        foreach($data as $column=>$type){
            if( $column == 'id' ){
                $sql .= "`$column` $type NOT NULL PRIMARY KEY AUTO_INCREMENT,";
            }else{
                $sql .= "$column $type,";
            }
        }
        if( $key ){
            foreach( $key as $field=>$type ){
                $sql .= "{$type} (`{$field}`),";
            }
        }

        $sql = rtrim($sql,',');
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
        return $this->query($sql);
    }

    public function dropTable($table)
    {
        
    }

    /**
     * get last error info
     */
    public function errorInfo()
    {
        return $this->_pdo->errorInfo();
    }

    /**
     * get last insert id
     */
    public function lastInsertId()
    {
        return $this->_pdo->lastInsertId();
    }

    /**
     * get debug params
     */
    public function debugDumpParams()
    {
        return $this->_sth->debugDumpParams();
    }

}
