<?php
namespace amber\modules;

class Log
{
    /**
     * log配置
     * 配置主要是设定日志命名以及存放的位置，比如
     * 
     * array(
     *  'path' => '/data/logs/php/%s-%date{Y-m-d}.log',
     *  'level' => 'info'
     * );
     * 
     * return $config;
     */
    protected $config;

    /**
     * today 存储的始终是今天00:00:00的unix时间戳
     * 一个脚本可能有多个putp操作，因此需要针对不同的log储存对应时间状态
     */
    protected $today = array();

    /**
     * 初始化配置
    */
    public function __construct($config)
    {
        if (empty($config)) {
            throw new \Exception("Log 配置为空", 1);
        }
        $this->config = $config;
    }

    /**
     * 写入log操作，会添加时间标记以及换行符号，方便对日志进行分析
     * 强烈建议日志内容统一使用json格式，无论是机器还是人都很方便阅读
     * @param $key string log 标识符
     * @param $val string log 内容
    */
    public function put($key, $value, $level = '' )
    {
        if ($this->canRecord($level)) {
            $logPath = $this->getLogPath($key, true);
            $log = sprintf("%s [%s] | %s\n", date('Y-m-d H:i:s'), $level, rtrim($value));
            return file_put_contents($logPath, $log, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * put plus
     * 带有log自动归档的的日志记录方法
     * 支持非常驻内存的程序自动切割
     * @return void
     */
    public function putp($key, $value, $level = '' )
    {
        if ($this->canRecord($level)) {
            $logPath = $this->getLogPath($key, false);
            $logMetaPath = $this->getLogPath('.' . $key, false);
            $log = sprintf("%s [%s] | %s\n", date('Y-m-d H:i:s'), $level, rtrim($value));
            file_put_contents($logPath, $log, FILE_APPEND | LOCK_EX);
            $this->cutLog($key, $logPath, $logMetaPath);
        }
    }

    /**
     * 切割日志方法
     * @param  string $key            日志key
     * @param  string $logPath        日志路径
     * @param  string $logMetaPath 保存创建时间的文件
     * @return void                
     */
    protected function cutLog($key, $logPath, $logMetaPath = null)
    {
        if (PHP_SAPI == 'cli') {
            if (!isset($this->today[$key])) {
                $this->today[$key] = strtotime(date("Y-m-d"));
            }
        } else {
            if (is_file($logMetaPath)) {
                $this->today[$key] = strtotime(date('Y-m-d', filemtime($logMetaPath)));
            } else {
                $this->today[$key] = strtotime(date("Y-m-d"));
                file_put_contents($logMetaPath, time());
            }
        }

        //切割日志
        if (time() - $this->today[$key] > 86400) {
            $suffix = date('Y-m-d', $this->today[$key]);
            $archivePath = $this->getArchivePath(str_replace('.log', "-{$suffix}.log", $logPath));
            if (!file_exists($archivePath)) {
                // 在到达切割日志的时刻，不允许有两个或两个以上的进程进入临界区，因此进程之间需要互斥
                // 这里使用文件锁来达到互斥的目的
                $fp = fopen($logPath . '.lock', 'w');
                if (!flock($fp, LOCK_EX | LOCK_NB)) {
                    $this->today[$key] = strtotime(date('Y-m-d'));
                    return ;
                }
                rename($logPath, $archivePath);
                flock($fp, LOCK_UN);
                fclose($fp);
            }
            $this->today[$key] = strtotime(date('Y-m-d'));
            //修改保存创建时间的文件
            if (PHP_SAPI != 'cli') {
                file_put_contents($logMetaPath, time());
            }
        }
    }

    /**
     * 根据key获取log路径
     * @param string $key 日志名称
     * @param string $withDate 解析配置中的日期配置
     * @return string 日志绝对路径
    */
    public function getLogPath($key, $withDate = true)
    {
        if (!isset($this->config['path'])) {
            throw new \Exception("请配置日志路径", 1);
        }
        //提取日志规则
        $path = $this->config['path'];

        if (!$withDate) {
            if (false !== $datePosition = strpos($path, '%date{')) {
                $path =  substr($path, 0, $datePosition);
                $path = rtrim($path, '-');
            }
            $path = rtrim($path, '.log');
            return str_replace('%s', $key, $path) . '.log';
        }

        //进行日期替换        
        if (false !== $start = strpos($path, '%date{')) {
            $start += 6;
            $end = strpos($path, '}',$start);
            $dataRule = substr($path, $start, $end-$start); 
            $path = str_replace('%date{', '', $path);
            $path = str_replace($dataRule, date($dataRule), $path);
            $path = str_replace('}', '', $path);
        }

        //进行日志名称替换
        return str_replace('%s', $key, $path);
    }

    /**
     * 获取日志归档路径
     * 由于是在第二天才会进行归档，因此需要获取前一天的数据
     * @param  string $logPath
     * @return 
     */
    protected function getArchivePath($logPath, $time = null)
    {
        $dir = dirname($logPath);
        $time = $time ? $time - 86400 : time() - 86400;
        $y = date('Y', $time);
        $m = date('m', $time);
        if (!file_exists($dir .'/'. $y)) {
            mkdir($dir .'/'. $y);
        }
        if (!file_exists($dir .'/'. $y . '/' . $m)) {
            mkdir($dir .'/'. $y . '/' . $m);
        }
        return $dir .'/'. $y . '/' . $m . '/'  .basename($logPath);
    }

    /**
     * 判断当前级别是否能够被记录
     */
    protected function canRecord($levelString)
    {
        if( $this->config['level'] == 'off'){
            return false;
        }

        if( $this->config['level'] == 'all' || $levelString == '' ){
            return true;
        }

        $level = array(
            'debug'  => 0,
            'info'   => 1,
            'notice' => 2,
            'warn'   => 3,
            'error'  => 4,
            'fatal'  => 5
            );
        if (!array_key_exists($levelString, $level)){
            return false;
        }
        if ($level[$levelString] >= $level[$this->config['level']]){
            return true;
        }
        return false;
    }

}