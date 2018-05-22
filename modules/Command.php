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

	public function main()
	{
		if (!($command = $this->getcmd())) {
			$this->help();
			return ;
		}
		$this->projectName = $command['arg'];
		$this->projectDir = $this->cwd . '/' . $this->projectName;
		if (file_exists($this->projectDir)) {
			throw new \Exception("项目已经存在", 1);
		}

		if (!mkdir($this->projectDir) || !mkdir($this->projectDir . '/amber')) {
			throw new \Exception("创建文件夹失败", 1);
			return ;
		}
		//复制amber目录
		$this->File->copyr(dirname(__DIR__), $this->projectDir . '/amber' );
		//复制项目文件
		$this->File->copyr(dirname(__DIR__) . '/examples/site', $this->projectDir);
		//建立一个空项目
		mkdir($this->projectDir . '/app/' . $this->projectName);

	}

	/**
	 * 帮助信息
	 */
	protected function help()
	{
		$help = array(
			"amber\tcreate\t[peoject name]" => '在当前的目录下，使用amber创建一个项目，这将会创建一个完整的amber项目',
			"amber\tapi\t[project name]" => '创建一个api项目，将不会加载css和js文件'
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
			'create',
			'startapi'
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