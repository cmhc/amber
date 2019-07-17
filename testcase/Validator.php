<?php
/**
 * 验证器测试
 */
require_once __DIR__ . '/load.php';
use amber\modules\Validator;

class validatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * 测试参数验证方法
     * @return
     */
    public function testCheckParams()
    {
        $Validator = new Validator\Parameter();
        $rules = array(
            'a' => 'ne|numeric'
        );
        $expected = array(
            'a' => 1
        );
        $Validator->setRules($rules);
        $this->assertTrue($Validator->validate($expected));

        // 验证错误的参数
        $unexpected = array(
            'a' => 'string'
        );
        try {
            $msg = '';
            $Validator->validate($unexpected);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }
        $this->assertEquals('params error, expected param a', $msg);
    }

    /**
     * 测试带有签名包装器的参数验证
     * @return 
     */
    public function testSign()
    {
        $SignatureValidator = new Validator\MD5Signature(new Validator\Parameter());
        $rules = array(
            'a' => 'ne|numeric',
            'sign' => 'ne'
        );
        $unexpected = array(
            'a' => 1
        );
        $SignatureValidator->setRules($rules);
        $SignatureValidator->setKey('key');
        try {
            $SignatureValidator->validate($unexpected);
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }
        $this->assertEquals('params error, expected param sign', $msg);

        // 签名错误验证
        $unexpected = array(
            'a' => 1,
            'sign' => 'this is error sign'
        );
        try {
            $SignatureValidator->validate($unexpected);
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }
        $this->assertEquals('sign error', $msg);

        // 验证正确的签名
        $expected = array(
            'a' => 1
        );
        $expected['sign'] = amber\modules\Signature\Signature::md5($expected)->sign('key');
        $this->assertTrue($SignatureValidator->validate($expected));
    }

    /**
     * 测试外观
     * @return
     */
    public function testFacades()
    {
        $Validator = new Validator\Validator();
        $rules = array(
            'a' => 'ne|numeric'
        );
        $expected = array(
            'a' => 1
        );
        
        $this->assertTrue($Validator->validate($expected, $rules));

        // 验证错误的参数
        $unexpected = array(
            'a' => 'string'
        );
        try {
            $msg = '';
            $Validator->validate($unexpected, $rules);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }
        $this->assertEquals('params error, expected param a', $msg);
    }

}