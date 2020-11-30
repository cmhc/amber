<?php
namespace amber\modules\Validator;

/**
 * 装饰者：参数验证器
 */
class Parameter extends Base
{
    protected $allowEmpty = false;

    /**
     * 验证规则
     * @var array
     */
    protected $rules;

    /**
     * 参数验证
     * @param  array $params array
     * @return boolean
     * @throws  Exception
     */
    public function validate($params)
    {
        foreach ($this->rules as $key => $rule) {
            $value = $this->getParamValue($params, $key);
            if (!$this->validationRule($value, $rule)) {
                throw new \Exception("params error, expected param {$key}", 1);
            }
        }
        return true;
    }

    /**
     * 设置规则
     * @param array $rules
     * @return void
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * 根据规则验证
     * @param string $value 等待验证的值
     * @param string $rule 验证规则
     * @return bool
     * @throws Exception
     */
    protected function validationRule($value, $rule)
    {
        // rule可能带有正则，分割的时候绕开转义
        $rules = preg_split('/(?<!\\\)\|/', $rule);
        foreach ($rules as $rule) {
            $pattern = '';
            if (strpos($rule, ':') !== false) {
                $ruleParts = explode(':', $rule);
                $pattern = trim($ruleParts[1]);
                $rule = trim($ruleParts[0]);
            }
            $validatorMethod = 'is' . ucfirst($rule);
            if (!method_exists($this, $validatorMethod)) {
                throw new Exception("validator {$rule} not exists", 1);
            }
            // 常规验证器验证
            if (!$this->$validatorMethod($value)) {
                return false;
            }
            // 正则验证
            if ($pattern && !$this->regularPass($value, $pattern)) {
                return false;
            }
            if (empty($value) && $this->allowEmpty) {
                $this->allowEmpty = false;
                return true;
            }
        }
        return true;
    }

    /**
     * 根据参数key获取对应的值
     * @param  array $params 等待验证的参数
     * @param  string $ruleKey 规则key
     * @return mixed
     */
    protected function getParamValue($params, $ruleKey)
    {
        if (!$params) {
            return false;
        }
        foreach(explode('.', $ruleKey) as $key) {
            if (isset($params[$key])) {
                $params = $params[$key];
            } else {
                return false;
            }
        }
        return $params;
    }

    /**
     * 判断是否是个数字
     * @param $value
     * @return boolean
     */
    protected function isNumeric($value)
    {
        return is_numeric($value);
    }

    /**
     * 判断是否为空
     * @param $value
     * @return boolean
     */
    protected function isNe($value)
    {
        return !empty($value);
    }

    /**
     * 允许为空
     * @param $value
     * @return boolean
     */
    protected function isAllowEmpty($value)
    {
        $this->allowEmpty = true;
        return true;
    }

    /**
     * 测试是否能够匹配
     * @param $value
     * @param $pattern
     * @return boolean
     */
    protected function regularPass($value, $pattern)
    {
        return preg_match($pattern, $value);
    }
}