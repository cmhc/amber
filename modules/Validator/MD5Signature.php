<?php
namespace amber\modules\Validator;
use amber\modules\Signature\Signature;

/**
 * 装饰者，验证器
 */
class MD5Signature extends Base
{
    /**
     * @param amber\modules\Validator\Base $Validator
     */
    public function __construct(Base $Validator)
    {
        $this->Validator = $Validator;
    }

    /**
     * 设置签名
     * @param  string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * 验证规则
     * @return boolean
     */
    public function validate($params)
    {
        $this->Validator->validate($params);
        $this->verifySign($params);
        return true;
    }

    /**
     * 设置规则
     * @param array $rules
     */
    public function setRules($rules)
    {
        $this->Validator->setRules($rules);
        return $this;
    }

    /**
     * 验证参数中的签名
     * @return boolean
     */
    public function verifySign($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        if (!Signature::md5($params)->verify($sign, $this->key)) {
            throw new \Exception('sign error', 1);
        }
        return true;
    }
}