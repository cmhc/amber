<?php
namespace amber\modules\Validator;

/**
 * 验证器基类
 */
abstract class Base
{
    /**
     * 验证
     * @return boolean
     */
    public abstract function validate($params);

    /**
     * 设置规则
     * @param $rules 
     * @return  void
     */
    public abstract function setRules($rules);
}
