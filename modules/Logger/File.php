<?php
/**
 * 文件方式实现的日志记录器
 */

namespace amber\modules\Logger;

class File
{
    /**
     * log配置
     * 配置主要是设定日志命名以及存放的位置，比如
     * 
     * array(
     *  'dir' => '/data/logs/php/',
     *  'level' => File::ALL ^ File::DEBUG
     * );
     * 
     * return $config;
     */
    protected $config;

    /**
     * 日志级别
     */
    const ALL       = 0xFF;
    const OFF       = 0;
    const DEBUG     = 1;
    const INFO      = 2;
    const NOTICE    = 4;
    const WARNING   = 8;
    const ERROR     = 16;
    const FATAL     = 32;

    protected $methods;

    /**
     * 初始化配置
    */
    public function __construct($config)
    {
        if (empty($config)) {
            throw new \Exception("Log 配置为空", 1);
        }
        // 设置默认日志记录级别
        if (!isset($config['level'])) {
            $config['level'] = self::ALL ^ self::DEBUG;
        }
        // 设置记录方法
        $this->methods = array(
            'debug'     => self::DEBUG,
            'info'      => self::INFO,
            'notice'    => self::NOTICE,
            'warning'   => self::WARNING,
            'error'     => self::ERROR,
            'fatal'     => self::FATAL
        );
        $this->config = $config;
    }

    /**
     * 实现 debug info ...等方法
     * @return void
     */
    public function __call($method, $args)
    {
        if (!isset($this->methods[$method])) {
            throw new \Exception("调用方法不存在", 1);
        }
        if (!isset($args[1]) || isset($args[2])) {
            throw new Exception("参数不正确", 1);
        }

        $this->log($args[0], $args[1], $this->methods[$method]);
    }

    /**
     * 带有log自动归档的的日志记录方法
     * 支持非常驻内存的程序自动切割
     * @param  string $key
     * @param  string $value
     * @param  int    $level
     * @return void
     */
    public function log($key, $value, $level)
    {
        if (!$this->canRecord($level)) {
            return ;
        }
        $logPath = $this->getLogPath($key);
        $content = sprintf("%s %s %s\n", date('Y-m-d H:i:s'), $level, rtrim($value));
        file_put_contents($logPath, $content, FILE_APPEND | LOCK_EX);
    }

    /**
     * 根据key获取log路径
     * @param string $key 日志名称
     * @param string $withDate 解析配置中的日期配置
     * @return string 日志绝对路径
    */
    public function getLogPath($key)
    {
        if (!isset($this->config['dir'])) {
            throw new \Exception("请配置日志路径", 1);
        }
        return rtrim($this->config['dir'], '/') . '/' . $key . '.log';
    }

    /**
     * 判断当前级别是否能够被记录
     * @param  string $levelString
     */
    protected function canRecord($level)
    {
        if (($this->config['level'] & $level) == 0) {
            return false;
        }
        return true;
    }

}