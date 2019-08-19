<?php
/**
 * 日志类测试
 */
require_once __DIR__ . '/load.php';

/**
 * 待测试类
 */
class LoggerForTest extends amber\modules\Logger\File
{

    public function getArchivePath($logFile, $time)
    {
        return parent::getArchivePath($logFile, $time);
    }

    public function canRecord($level)
    {
        return parent::canRecord($level);
    }
}

class LoggerTest extends PHPUnit_Framework_TestCase
{

    /**
     * 测试获取日志路径
     */
    public function testGetLogPath()
    {
        $Logger = new LoggerForTest(array(
            'dir' => 'test/path/'
        ));
        $path = $Logger->getLogPath('hello');
        $this->assertEquals('test/path/hello.log', $path);
    }

    /**
     * 测试能否被记录
     * @param  stirng $levelString
     * @return void
     */
    public function testCanRecord()
    {
        // 将记录的日志级别一个一个排除，测试预期的记录情况
        $levelArr = array(
            LoggerForTest::DEBUG => array(
                false, true, true, true, true, true
            ),
            LoggerForTest::INFO => array(
                false, false, true, true, true, true
            ),
            LoggerForTest::NOTICE => array(
                false, false, false, true, true, true
            ),
            LoggerForTest::WARNING =>array(
                false, false, false, false, true, true
            ),
            LoggerForTest::ERROR => array(
                false, false, false, false, false, true
            ),
            LoggerForTest::FATAL => array(
                false, false, false, false, false, false
            )
        );

        $setLevel = LoggerForTest::ALL;
        foreach ($levelArr as $level => $result) {
            $setLevel = $setLevel ^ $level;
            $Logger = new LoggerForTest(array(
                'dir' => 'test/path',
                'level' => $setLevel
            ));
            $this->assertEquals($result[0], $Logger->canRecord(LoggerForTest::DEBUG), 'debug');
            $this->assertEquals($result[1], $Logger->canRecord(LoggerForTest::INFO), 'info');
            $this->assertEquals($result[2], $Logger->canRecord(LoggerForTest::NOTICE), 'notice');
            $this->assertEquals($result[3], $Logger->canRecord(LoggerForTest::WARNING), 'warning');
            $this->assertEquals($result[4], $Logger->canRecord(LoggerForTest::ERROR), 'error');
            $this->assertEquals($result[5], $Logger->canRecord(LoggerForTest::FATAL), 'fatal');
        }
    }

}