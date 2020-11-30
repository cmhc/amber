<?php
namespace amber\modules\Signature;

class Signature
{
    /**
     * md5签名验签类
     * @param  mixed $data
     * @return Signature
     */
    public static function md5($data)
    {
        return new MD5($data);
    }

    /**
     * rsa签名验签
     * @param  mixed $data
     * @return Signature
     */
    public static function rsa($data)
    {
        return new RSA($data);
    }
}