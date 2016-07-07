<?php
namespace cmhc\amber\modules;

/**
 * 简单数据库操作类，基于pdo
 * update 14:35 2015/3/13  查询出错直接返回false，不再有fetch操作
 */
class DB
{

    private $_pdo = null; //pdo操作实例

    //执行操作句柄
    private $_sth = null;

    /**
     * 数据库配置
     * array
     */
    private $config;

    private static $handle = array();

    /**
     * 初始化连接配置,支持从文件中读取config同时也支持传入config内容
     * @param  array $config  配置数组
     */
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

    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * 初始化数据库连接
     * @return object pdo对象
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
     * 获取一行数据,sql语句最好加上 limit 1
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

        //查询失败直接返回false
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
     * 获取单个数据
     */
    public function getVar($sql, $prepare = '')
    {
        //debug信息
        if (class_exists("Debug")) {
            Debug::msg(array('获取单个值SQL' . __FILE__ . __LINE__ . '行', $sql), 1);
        }

        $result = $this->query($sql, $prepare, \PDO::FETCH_NUM, 'fetch');
        return $result[0];

    }

    /**
     * 获取一行数据
     * @param $sql string sql语句
     * @param $prepare array 可选，参数存在则绑定前面的sql语句里面的占位符
     */
    public function getRow($sql, $prepare = '')
    {
        //debug信息
        Debug::msg(array('获取ROW SQL' . __FILE__ . __LINE__ . '行', $sql), 1);

        $result = $this->query($sql, $prepare, \PDO::FETCH_ASSOC, 'fetch');
        return $result;
    }

    /**
     * 获取所有数据
     * @param $sql string sql语句
     * @param $prepare array 可选，参数存在则绑定前面的sql语句里面的占位符
     * @param $style fetch样式，废弃
     */
    public function getAll($sql, $prepare = '', $style = '')
    {
        $result = $this->query($sql, $prepare, $style, 'fetchAll');
        return $result;
    }

    /**
     * 执行，返回影响的函数，sql需要注意严格过滤，该函数不对sql过滤
     * 因此不要有用户输入的数据在此sql里面
     * @param $sql
     * @return int 返回所影响的函数
     */
    public function exec($sql)
    {
        return $this->_pdo->exec($sql);

    }

    /**
     * 插入操作
     * @param  string $table 表名称
     * @param  array  $data  数据
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
     * 更新操作
     */
    public function update($table, $data, $where)
    {
        /* 对whwere进行测试,不允许没有条件的更新 */
        $whereArray = explode("=", $where);
        if (count($whereArray) < 2 || $whereArray[1] == '') {
            throw new Exception("不允许没有条件的更新", 1);
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
     * 返回错误信息
     */
    public function errorInfo()
    {
        return $this->_pdo->errorInfo();
    }

    /**
     * 上一个自增id
     */
    public function lastInsertId()
    {
        return $this->_pdo->lastInsertId();
    }

    /**
     * 打印debug信息
     */
    public function debugDumpParams()
    {
        return $this->_sth->debugDumpParams();
    }

}
