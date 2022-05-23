<?php
/**
 * 自定义条件类
 * true&&false||true  => true
 * true&&false||false => false
 * 
 * @Author: huchao
 * @Date:   2020-04-19 18:04:58
 * @Last Modified by:   huchao
 * @Last Modified time: 2020-05-09 23:57:32
 */

namespace amber\modules\condition;

class Condition
{
    /**
     * @var callable
    */
    protected $callback;

    public function setCallback(callable $cb)
    {
        $this->callback = $cb;
    }

    /**
     * 解析条件字符串 
     */
    public function parse($condstr)
    {
        $conditions = array();
        $sections = explode("||", $condstr);
        foreach ($sections as $section) {
            $conditions[] = explode("&&", $section);
        }
        return $conditions;
    }

    /**
     * 外部为or，一个通过则所有的通过
     */
    public function pass(array $conditions)
    {
        foreach ($conditions as $section) {
            if ($this->sectionPass($section)) {
                return true;
            }
        }
        return false;
    }

    /**
     * section pass
     * 内部为and，一个通过则所有通过
    */
    public function sectionPass(array $items)
    {
        if (!$this->callback) {
            throw new \Exception("需要设置条件判断函数", 1);
        }
        
        $cb = $this->callback;
        foreach ($items as $item) {
            if ($cb($item) === false) {
                return false;
            }
        }
        return true;
    }
}