<?php
/**
 * 命令行模块
 * amber startapp yourapp
 * amber startapi yourapi
 * 
 */
namespace amber\modules;

class Command
{
    protected $projectName;

    public function __construct()
    {
        $this->cwd = getcwd();
        $this->File = new File();
    }

    /**
     * 入口
     */
    public function main()
    {
        if (!($command = $this->getcmd())) {
            $this->help();
            return ;
        }
        switch ($command['cmd']) {
            case 'create':
                $this->createProject($command['arg']);
                echo "创建成功\n";
            break;
            case 'update':
                $this->updateProject($command['arg']);
                echo "更新成功\n";
            break;
            default:
                echo "暂无此命令";
        }
    }

    /**
     * 新项目
     * @return
     */
    protected function createProject($name)
    {
        $projectDir = $this->cwd . '/' . $name;
        if (file_exists($projectDir)) {
            throw new \Exception("项目已经存在", 1);
        }
        //复制项目文件
        $this->File->copyr(dirname(__DIR__) . '/examples/site', $projectDir);
    }

    /**
     * 更新项目
     * @return
     */
    protected function updateProject($name)
    {
        echo $projectDir = $this->cwd . '/' . $name;
        if (!file_exists($projectDir)) {
            throw new \Exception("项目不存在", 1);
        }
    }

    /**
     * 帮助信息
     */
    protected function help()
    {
        $help = array(
            "amber\tcreate\t[peoject name]" => '在当前的目录下，使用amber创建一个项目，这将会创建一个完整的amber项目',
            "amber\tupdate\t[project name]" => '创建一个api项目，将不会加载css和js文件'
        );
        foreach ($help as $cmd=>$msg) {
            echo $cmd ."\t". $msg . "\n";
        }
    }

    /**
     * 获取命令
     */
    protected function getcmd()
    {
        if ($GLOBALS['argc'] < 2) {
            return false;
        }
        $commands = array(
            'create', //根据amber创建新的项目
            'update' //更新amber, 会完全覆盖amber框架
        );
        if(!in_array($GLOBALS['argv'][1], $commands)) {
            return false;
        }
        return array(
            'cmd' => $GLOBALS['argv'][1],
            'arg' => $GLOBALS['argv'][2]
        );
    }

}