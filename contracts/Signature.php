<?php
namespace amber\contracts;

/**
 * 签名和验签
 */
interface Signature
{
    /**
     * 设置等待签名数据
     * @param  mixed $data
     */
    public function setData($data);

    /**
     * 签名
     * @param  string $privateKey
     * @return string
     */
    public function sign($privateKey);

    /**
     * 验签
     * @param  string $signature
     * @param  string $publicKey
     * @return boolean
     */
    public function verify($signature, $publicKey);
}