<?php
namespace amber\modules\Signature;

abstract class Base
{
    protected $data;

    public function __construct($data)
    {
        $this->setData($data);
    }
    
    /**
     * 数组转字符串
     * @param  array $data
     * @return $this
     */
    public function setData($data)
    {
        if (is_array($data)) {
            $this->data = $this->arr2str($data);
        } else {
            $this->data = $data;
        }
        return $this;
    }

    /**
     * 数组转字符串
     * @param  array $array
     * @return array
     */
    protected function arr2str($array)
    {
        ksort($array);
        $parts = array();
        foreach ($array as $key => $value) {
            $parts[] = $key . '=' . $value;
        }
        return implode('&' , $parts);
    }
}