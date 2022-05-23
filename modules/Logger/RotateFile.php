<?php
/**
 * 自动切割的文件日志实现
 */

namespace amber\modules\Logger;

class RotateFile
{
    protected $today = array();


    public function __construct(File $Logger)
    {
        $this->Logger = $Logger;
    }

    /**
     * 装饰Logger/File的日志记录方法
     * @param  string $method
     * @param  array $args
     * @return 
     */
    public function __call($method, $args)
    {
        if (!isset($args[1]) || isset($args[2])) {
            throw new Exception("参数不正确", 1);
        }
        $key = $args[0];
        $content = $args[1];

        $this->Logger->$method($key, $content);
        // 切割日志方法
        $this->rotate($key);
    }

    /**
     * 切割日志方法
     * @param  string $key
     * @return void
     */
    protected function rotate($key)
    {
        if (PHP_SAPI == 'cli') {
            $this->rotateInCLI($key);
        } else {
            $this->rotateInCGI($key);
        }
    }

    /**
     * cli环境下初始化当天日期
     * @return
     */
    protected function rotateInCLI($key)
    {
        if (!isset($this->today[$key])) {
            $this->today[$key] = strtotime(date("Y-m-d"));
        }

        // 还在当日，不切割
        if (time() - $this->today[$key] <= 86400) {
            return ;
        }

        $logPath = $this->Logger->getLogPath($key);
        $this->createArchiveLog($logPath, $this->today[$key]);

        $this->today[$key] = strtotime(date('Y-m-d'));
        return ;
    }

    /**
     * cgi环境下初始化当天日期
     * @return
     */
    protected function rotateInCGI($key)
    {
        $logPath = $this->Logger->getLogPath($key);
        $logMetaPath = dirname($logPath) . '/.' . basename($logPath);
        if (is_file($logMetaPath)) {
            $this->today[$key] = strtotime(date('Y-m-d', filemtime($logMetaPath)));
        } else {
            $this->today[$key] = strtotime(date("Y-m-d"));
            file_put_contents($logMetaPath, time());
        }

        // 还在当日，不切割
        if (time() - $this->today[$key] <= 86400) {
            return ;
        }

        $this->createArchiveLog($logPath, $this->today[$key]);
        $this->today[$key] = strtotime(date('Y-m-d'));
    }

    /**
     * 创建归档路径
     * @param  string $key   写入日志的key
     * @param  int $today 今日时间戳
     * @return void
     */
    protected function createArchiveLog($logPath, $today)
    {
        $archivePath = $this->getArchivePath($logPath, $today);
        $this->createArchiveDir($archivePath);
        // 在到达切割日志的时刻，不允许有两个或两个以上的进程进入临界区，因此进程之间需要互斥
        // 这里使用文件锁来达到互斥的目的
        if (!file_exists($archivePath)) {
            $fp = fopen($logPath . '.lock', 'w');
            if (!flock($fp, LOCK_EX | LOCK_NB)) {
                return ;
            }
            rename($logPath, $archivePath);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        return ;
    }

    /**
     * 获取日志归档完整路径
     * 由于是在第二天才会进行归档，因此需要获取前一天的数据
     * @param  string $logFile
     * @return 
     */
    protected function getArchivePath($logFile, $time)
    {
        $suffix = date('Y-m-d', $time);
        $archiveFile = str_replace('.log', "-{$suffix}.log", $logFile);
        $dir = dirname($logFile);
        $year = date('Y', $time);
        $month = date('m', $time);
        return sprintf("%s/%s/%s/%s", $dir, $year, $month, basename($archiveFile));
    }

    /**
     * 创建归档文件夹
     * @param  string $logFile
     * @param  string $time
     * @return boolean
     */
    protected function createArchiveDir($archivePath)
    {
        $archiveDir = dirname($archivePath);
        if (!file_exists($archiveDir)) {
            return mkdir($archiveDir, 0775, true);
        }
        return false;
    }
}