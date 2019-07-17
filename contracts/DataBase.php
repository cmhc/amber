<?php
namespace amber\contracts;

/**
 * 数据库接口
 */
interface Database
{

    /**
     * 查询表是否存在
     * @param  stirng $table
     * @return boolean
     */
    public function tableExists($table = null);

    /**
     * 获取所有的表
     * @return array
     */
    public function getTables();

    /**
     * 创建表
     * @return boolean
     */
    public function createTable();

    /**
     * 更新表
     * @return boolean
     */
    public function updateTable();

    /**
     * 删除表
     * @return boolean
     */
    public function deleteTable();

    /**
     * 优化表
     * @return boolean
     */
    public function optmize();

    /**
     * 开始事务
     * @return boolean
     */
    public function beginTransaction();

    /**
     * 提交事务
     * @return boolean
     */
    public function commit();

    /**
     * 回滚事务
     * @return boolean
     */
    public function rollback();

    /**
     * 插入数据
     * @param  array $data   数据
     * @param  string $table 可选的表名称，默认为当前表
     * @return boolean
     */
    public function insert($data, $table = null);

    /**
     * 删除数据
     * @param  string $where 条件
     * @param  array $bind 绑定的数据
     * @return boolean
     */
    public function delete($where, $bind);

    /**
     * 更新数据
     * @param  array $data 数据
     * @param  string $where 条件
     * @param  string $bind 条件绑定的数据
     * @return boolean
     */
    public function update($data, $where);

    public function get($where, $bind);

    public function getVar($field, $where, $bind);

    public function getVars($fields, $where, $bind);

    public function gets($where, $bind, $limit, $order = '');

    public function union($where, $bind, $limit, $order);

    /**
     * 列出一些行
     * @param  integer $page
     * @param  integer $perpage
     * @param  string  $orderby
     * @param  string  $order
     * @return array
     */
    public function lists($page = 1, $perpage = 20, $orderby = 'id', $order = 'ASC');

    /**
     * 当前表的行数
     * @return int
     */
    public function count();

    /**
     * 错误信息
     * @return array
     */
    public function errorInfo();

    /**
     * 执行语句的错误信息
     * @return array
     */
    public function statementErrorInfo();

}