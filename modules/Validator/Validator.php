<?php
namespace amber\modules\Validator;

/**
 * 被装饰者：参数验证器
 */
class Validator
{
    public static function validate($params, $rules)
    {
        $Validator = new Parameter();
        return $Validator->setRules($rules)->validate($params);
    }
}