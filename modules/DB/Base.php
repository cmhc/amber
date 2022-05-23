<?php

/**
 * DB Base
 * @Author: huchao06
 * @Date:   2019-08-31 11:15:55
 * @Last Modified by:   huchao06
 * @Last Modified time: 2019-11-17 00:20:25
 */

namespace amber\modules\DB;

abstract class Base
{
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

    /**
     * @param \PDO $Connection pdo连接
     */
    public function __construct(\PDO $Connection)
    {
        $this->Connection = $Connection;
    }

    /**
     * 插入数据
     * @param  array $data
     * @param  string $table
     * @return boolean
     */
    public function insert($data, $table = null)
    {
        $table = $table ? $table : $this->getTableName();
        // 根据scheme重新包装数据
        $insertData = array();
        foreach ($this->getScheme() as $key=>$type) {
            // 允许主键为空，可能为自增主键
            if (empty($data[$key]) && $key == $this->getPrimaryKey()) {
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
        $res = $sth->execute($data);
        $this->errorHandle($sth);
        return $res;
    }

    /**
     * 更新操作
     * @param  array $data
     * @param  string $where
     * @return  int 影响的行数
     * @throws Execption 更新失败抛出异常
     */
    public function update($data, $where, $bind = array())
    {
        // 对whwere进行测试,不允许没有条件的更新
        $whereArray = explode("=", $where);
        if (count($whereArray) < 2 || $whereArray[1] == '') {
            throw new \Exception("where 条件不允许为空", 1);
        }

        // 根据scheme重新包装数据
        $updateData = array();
        foreach ($this->getScheme() as $key=>$type) {
            // 不允许更新主键
            if ($key == $this->getPrimaryKey()) {
                continue;
            }
            // 不允许更新scheme不存在的键
            if (!isset($data[$key])) {
                continue;
            }
            $updateData[$key] = $data[$key];
        }
        //拼装set，组装更新数组
        $data = array();
        $set = '';
        foreach ($updateData as $f => $v) {
            $set .= " `{$f}`=:{$f},";
            if (strpos($f, ':') === false) {
                $data[':' . $f] = $v;
            } else {
                $data[$f] = $v;
            }
        }

        if (!empty($bind)) {
            foreach ($bind as $f => $v) {
                if (strpos($f, ':') === false) {
                    $data[':' . $f] = $v;
                } else {
                    $data[$f] = $v;
                }
            }
        }

        $set = rtrim($set, ',');
        $tableName = $this->getTableName();
        $sth = $this->Connection->prepare("UPDATE `{$tableName}` SET {$set} WHERE {$where}");
        $sth->execute($data);
        $this->errorHandle($sth);
        return $sth->rowCount();
    }

    /**
     * 删除操作
     * @param  string $where
     * @return  true
     * @throws Execption 删除出错的时候抛出异常
     */
    public function delete($where, $bind = array())
    {
        $tableName = $this->getTableName();
        $sth = $this->Connection->prepare("DELETE FROM {$tableName} WHERE {$where}");
        $sth->execute($bind);
        $this->errorHandle($sth);
        return true;
    }

    /**
     * 查询语句
     * @param  array $fields
     * @return $this
     */
    public function select($fields, $where, $bind, $order = '', $limit = '')
    {
        $tableName = $this->getTableName();
        if (is_array($fields)) {
            $fields = '`' . implode('`,`', $fields) . '`';
        }
        $sql = "SELECT $fields FROM {$tableName} WHERE {$where}";
        if ($order) {
            $sql .= " ORDER BY $order";
        }
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        $sth = $this->Connection->prepare($sql);
        $sth->execute($bind);
        $this->errorHandle($sth);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * in查询条件
     * @return
     */
    public function selectIn($fields, $field, $in)
    {
        $placeholder = rtrim(str_repeat('?,', count($in)), ',');
        $where = "`$field` in ($placeholder)";
        return $this->select($fields, $where, $in);
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
        $tableName = $this->getTableName();
        $query = '';
        for ($i=0; $i<$count; $i++) {
            $query .= "SELECT {$fields} FROM (SELECT {$fields} FROM `{$tableName}` WHERE {$where[$i]}";
            if ($order) {
                $query .= " ORDER BY {$order[$i]}";
            }
            if ($limit) {
                $query .= " LIMIT $limit[$i]";
            }
            $query .= ') as t UNION ALL ';
        }
        $query = rtrim($query, 'UNION ALL ');
        $sth = $this->Connection->prepare($query);
        $sth->execute($bind);
        $this->errorHandle($sth);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 联合查询
     * @param  string $fields
     * @param  array $where
     * @param  array $bind
     * @param  string $order
     * @param  string $limit
     * @return array
     */
    public function unionSelect($fields, $where, $bind, $order, $limit)
    {
        $count = count($where);
        $tableName = $this->getTableName();
        $query = '';
        for ($i=0; $i<$count; $i++) {
            $query .= "SELECT {$fields} FROM (SELECT {$fields} FROM `{$tableName}` WHERE {$where[$i]}";
            if ($order) {
                $query .= " ORDER BY {$order[$i]}";
            }
            if ($limit) {
                $query .= " LIMIT $limit[$i]";
            }
            $query .= ') as t UNION ALL ';
        }
        $query = rtrim($query, 'UNION ALL ');
        $sth = $this->Connection->prepare($query);
        $sth->execute($bind);
        $this->errorHandle($sth);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 错误处理
     * @param  PDOStatement $sth
     * @return void
     */
    public function errorHandle(\PDOStatement $sth)
    {
        $errorInfo = $sth->errorInfo();
        if ($errorInfo[2]) {
            throw new \Exception($errorInfo[2], 1);
        }
    }

    /**
     * 获取插入id
     */
    public function lastInsertId()
    {
        return $this->Connection->lastInsertId();
    }

}