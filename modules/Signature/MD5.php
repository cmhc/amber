<?php
namespace amber\modules\Signature;
use amber\contracts\Signature;

/**
 * md5签名和验签
 */
class MD5 extends Base implements Signature
{
    protected $data;

    /**
     * 签名
     * @param  string $privateKey
     * @return string
     */
    public function sign($privateKey)
    {
        return md5($this->data . $privateKey);
    }

    /**
     * 验签
     * @param  string $signature
     * @param  string $publicKey
     * @return boolean
     */
    public function verify($signature, $publicKey)
    {
        return (md5($this->data . $publicKey) == $signature);
    }
}