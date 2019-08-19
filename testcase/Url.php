<?php
/**
 * 测试url
 */
require_once __DIR__ . '/load.php';
use amber\modules\Util\Url;

class UrlTest extends PHPUnit_Framework_TestCase
{
    /**
     * 测试转换成绝对url
     * @return
     */
    public function testToAbs()
    {
        $abs = Url::toAbs('a.php', 'http://www.imhuchao.com');
        $this->assertEquals('http://www.imhuchao.com/a.php', $abs);

        $abs = Url::toAbs('../a.php', 'http://www.imhuchao.com/path/');
        $this->assertEquals('http://www.imhuchao.com/a.php', $abs);
    }
}