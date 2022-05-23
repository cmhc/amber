<?php
/**
 * 日志类测试
 */
require_once __DIR__ . '/load.php';
use amber\modules\Logger;
/**
 * 待测试类
 */
class RotatingLoggerForTest extends amber\modules\Logger\RotateFile
{
    public function __call($method, $args)
    {
        return call_user_func_array(array(parent, $method), $args);
    }

    public function setToday($key, $val)
    {
        $this->today[$key] = $val;
    }
}

class LoggerTest extends PHPUnit_Framework_TestCase
{
    /**
     * 初始化环境
     */
    public function __construct()
    {
        $this->Logger = new Logger\File(array(
            'dir' => __DIR__ . '/data/logs'
        ));
        $this->RotatingLogger = new RotatingLoggerForTest($this->Logger);
        $this->RotatingLogger->info('test', time());
    }

    /**
     * 测试获取日志路径
     * @return void
     */
    public function testGetArchivePath()
    {

        $path = $this->Logger->getLogPath('hello');
        $archivePath = $this->RotatingLogger->getArchivePath($path, strtotime('2019-08-18'));
        $this->assertEquals(__DIR__ . '/data/logs/2019/08/hello-2019-08-18.log', $archivePath);
    }

    /**
     * 测试创建归档文件夹
     * @return void
     */
    public function testCreateArchiveDir()
    {
        $path = $this->Logger->getLogPath('hello');
        $archivePath = $this->RotatingLogger->getArchivePath($path, strtotime('2019-08-18'));
        $this->RotatingLogger->createArchiveDir($archivePath);
        $this->assertTrue(file_exists(dirname($archivePath)));
    }

    /**
     * 测试cli下面的日志自动切割
     * @return  void
     */
    public function testRotateInCLI()
    {
        // 把时间往前移动一天
        $path = $this->Logger->getLogPath('test');
        $archivePath = $this->RotatingLogger->getArchivePath($path, time() - 86401);
        $this->RotatingLogger->setToday('test', time() - 86401);
        $this->RotatingLogger->rotateInCLI('test');
        $this->assertTrue(file_exists($archivePath));
    }

}