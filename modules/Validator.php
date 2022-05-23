<?php
namespace amber\modules;

/**
 * 被装饰者：参数验证器
 */
class Validator
{
    public static function validate($params, $rules)
    {
        $Validator = new Validator\Parameter();
        return $Validator->setRules($rules)->validate($params);
    }
}