<?php
/**
 * 字符串包含和不包含
 * 支持&&和||操作
 * true&&false||true  => true
 * true&&false||false => false
 * 
 * @Author: huchao
 * @Date:   2020-04-19 18:04:58
 * @Last Modified by:   huchao
 * @Last Modified time: 2020-05-09 23:57:32
 */

namespace amber\modules\condition;

class Str
{
    protected static $Condition;

    protected static $Str;

    /**
     * 表示条件满足这个字符串才通过 
     */
    public static function In($str, $cond)
    {
        self::$Str = $str;

        if (!self::$Condition) {
            self::$Condition = new Condition();
            self::$Condition->setCallback(function($cond) {
                if (empty($cond) && empty(self::$Str)) {
                    return true;
                }
                if (!empty($cond) && strpos(self::$Str, $cond) !== false) {
                    return true;
                }
                return false;
            });
        }

        if (self::$Condition->pass(self::$Condition->parse($cond))) {
            return true;
        }
        return false;
    }

    /**
     * 表示条件不满足这个字符串才通过 
     */
    public static function NotIn($str, $cond)
    {
        return !self::In($str, $cond);
    }
}