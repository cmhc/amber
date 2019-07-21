<?php
/**
 * user表
 */
namespace app\model\table;
use app\model\db;

class user extends db
{
    public $table = 'user';

    public $scheme = array(
        'fields' => array(
            'id' => 'integer',
            'name' => 'varchar(255) NOT NULL',
            'password' => 'varchar(255) NOT NULL',
            'nickname' => 'varchar(255) NOT NULL',
            'phone' => 'varchar(255) NOT NULL DEFAULT 0',
            'email' => 'varchar(255) NOT NULL DEFAULT 0',
            'sex' => 'tinyint(1) NOT NULL DEFAULT -1',
            'role' => 'varchar(255) NOT NULL DEFAULT user',
            'salt' => 'varchar(255) NOT NULL'
        ),
        'keys' => array(
            'name' => 'UNIQUE INDEX',
            'email' => 'UNIQUE INDEX'
        ),
    );

    public $i18n = array(
        'zh' => array(
            'id' => 'ID',
            'name' => '账号',
            'password' => '密码',
            'nickname' => '昵称',
            'phone' => '手机号',
            'email' => '邮箱',
            'sex' => '性别',
            'role' => '角色',
            'salt' => '盐'
        )
    );

    /**
     * 列出时候的数据
     * @return
     */
    public function onList($data)
    {
        foreach ($data as $key => $item) {
            $item['password'] = '***';
            $item['salt'] = '***';
            $data[$key] = $item;
        }
        return $data;
    }

    /**
     * 插入时候的动作
     * @return  array 返回需要插入的数据
     */
    public function onInsert($data)
    {
        $salt = mt_rand(1000,9999) . time();
        $data['salt'] = $salt;
        $data['password'] = md5($data['password'] . $salt);
        return $data;
    }

    /**
     * 更新之前的动作
     * @return array 返回需要插入的数据
     */
    public function onUpdate($data)
    {
        // 检查密码是否更改
        if (strlen($data['password']) == 32) {
            return $data;
        }
        $salt = mt_rand(1000, 9999) . time();
        $data['salt'] = $salt;
        $data['password'] = md5($data['password'] . $salt);
        return $data;
    }

}