<?php
/**
 * 签名工厂测试
 */
require_once __DIR__ . '/load.php';
use amber\modules\Signature\Signature;

class signatureTest extends PHPUnit_Framework_TestCase
{
    /**
     * 测试md5的签名和验签名
     * @return
     */
    public function testMD5()
    {
        $Signature = Signature::md5('123');
        $sig = $Signature->sign('key');
        $this->assertTrue($Signature->verify($sig, 'key'));
    }

    /**
     * 测试验证
     * @return
     */
    public function testRSA()
    {
        $Signature = Signature::rsa('123');
        $publicKey = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC7cLA46RpmOMX6NSJ+R5L697GC
XJ2vzDQFXzgzajomg2vYDkrxXWNLOmMqwDoyFs1GRT6NrQ1dkECmFOXOIZjT6fAG
0AHvYZR/Qz6T3wMYvDdaWWW0BzKnaGa4E7dnagvFJXS+V5TVBQsX2/GI2bkqFqiz
1VfTJxlnz57kSzHEPQIDAQAB
-----END PUBLIC KEY-----";
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC7cLA46RpmOMX6NSJ+R5L697GCXJ2vzDQFXzgzajomg2vYDkrx
XWNLOmMqwDoyFs1GRT6NrQ1dkECmFOXOIZjT6fAG0AHvYZR/Qz6T3wMYvDdaWWW0
BzKnaGa4E7dnagvFJXS+V5TVBQsX2/GI2bkqFqiz1VfTJxlnz57kSzHEPQIDAQAB
AoGBAIvy42Baix9vnEHokkx+3DsN3TdcN1Aew2iPY8LfuXMwBMFYSpRUCeMNQSWW
SN1FMRcadE4Lu0L0hZB7Yem6JAQuLtulmVKgdiw8aAXnK/bdPB8VE4ZcDFt4TufX
gsw75BtLeiX69usCovcSJDDlmUR7aB9qnyAthaSxC7DL1IxxAkEA3gpBLHsJ0yGY
vs9DiJTYZ/hLGD5Un65vmpTLc1D5Xc9/OSmBXiKoe07Prkg9vZSVrjPpUpiCTvjN
xcwrDzoMTwJBANgbtla4QydQ/ry/fncGcVV3JG0mTu/iLsftVoEpyOYVb9eMNXoJ
p3ncyyeYqVW5AQlcWr8GU8//hoU07KIrB7MCQGRrrt43J1Jdt39Ure5voxAis5Pb
XNp7Qe5frUQSMzXCSn/HzcKNWjWqhzMDaSj8slV/FN9OKmEdFbOHi1HvpvECQCcL
ssEuX6u93Zi6vJ3Cwz1e3mz+K+r5odwrjKKfqxWvL9rxEURwdBr3gpkv4wCDAaXw
UtEK0p/VGjf9HPgb8DMCQDsdttwbAPeJPhXB0wSIcaCMsehcsXPmMAM1O/aiSB0K
kzTeruRsjoInrZ9V+04XsuvJmGWiOY9Bc0JnIqxwLPM=
-----END RSA PRIVATE KEY-----";
        $sig = $Signature->sign($privateKey);
        $this->assertTrue($Signature->verify($sig, $publicKey));
    }
}