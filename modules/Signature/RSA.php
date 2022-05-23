<?php
namespace amber\modules\Signature;
use amber\contracts\Signature;

/**
 * rsa签名和验签
 */
class RSA extends Base implements Signature
{
    protected $data;

    /**
     * 签名
     * @param  string $privateKey
     * @return string
     */
    public function sign($privateKey)
    {
        openssl_sign($this->data, $signature, $privateKey);
        return base64_encode($signature);
    }

    /**
     * 验签
     * @param  string $signature
     * @param  string $publicKey
     * @return boolean
     */
    public function verify($signature, $publicKey)
    {
        $signature = base64_decode($signature);
        return (openssl_verify($this->data, $signature, $publicKey) == 1);
    }
}