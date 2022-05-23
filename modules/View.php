<?php
/**
 * 页面展示类
 * 提供了一个简单的模板视图
 */
namespace amber\modules;

class View
{
    protected $__viewPath = null;

    protected $__class = array();

    protected $__style = array();

    protected $__script = array();

    protected $__inlineStyle = '';

    protected $__inlineScript = '';

    public function __construct($viewPath)
    {
        $this->__viewPath = $viewPath;
    }

    /**
     * 前端页面访问一个不存在的属性返回空
     */
    public function __get($name)
    {
        return ;
    }

    /**
     * 前端可以调用使用addMethod注册过的方法
     * @param   $method 
     * @param   $args   
     * @return
     */
    public function __call($method, $args)
    {
        if(isset($this->__class[$method])){
            $class = $this->__class[$method];
            return $class::$method($args);
        }else{
            return false;
        }
    }

    /**
     * 赋值
     * @param  string $var
     * @param  mixed $value
     * @return
     */
    public function assign($var, $value)
    {
        $this->$var = $value;
    }

    /**
     * 注册一个前端可以调用的方法
     * 该方法必须是静态的方法
     */
    public function addMethod($method, $class)
    {
        $this->__class[$method] = $class;
    }

    /**
     * 载入模板文件
     * @param $view
     */
    public function display($view, $data = null)
    {
        $path = $this->__viewPath . '/' . $view;
        if (!file_exists($path)) {
            throw new \Exception("模板文件{$path}不存在", 1);
        }
        if ($data) {
            $this->assign('data', $data);
        }
        require $path;
    }

    /**
     * 获取模板目录
     * @return
     */
    public function getPath()
    {
        return $this->__viewPath;
    }

    /**
     * add script
     */
    public function addScript($uri, $group = 'global')
    {
        $this->__script[$group][$uri] = 1;
    }

    /**
     * 移除脚本
     */
    public function removeScript($uri, $group = 'global')
    {
        if (isset($this->__script[$group][$uri])) {
            unset($this->__script[$group][$uri]);
        }
    }

    /**
     * 添加内联脚本
     * @param  string $style 脚本内容
     */
    public function addInlineScript($script)
    {
        $this->__inlineScript .= $script;
    }

    /**
     * 添加样式
     * @param string $uri
     * @param string $group
     */
    public function addStyle($uri, $group = 'global')
    {
        $this->__style[$group][$uri] = 1;
    }

    /**
     * 移除样式
     * @param  string $uri
     * @param  string $group
     */
    public function removeStyle($uri, $group = 'global')
    {
        if (isset($this->__style[$group][$uri])) {
            unset($this->__style[$group][$uri]);
        }
    }

    /**
     * 添加内联样式
     * @param  string $style 样式内容
     */
    public function addInlineStyle($style)
    {
        $style = str_replace(array("\n", "\r", "\t"), "", $style);
        $this->__inlineStyle .= $style;
    }

    /**
     * 获取脚本
     * @param string $group 
     */
    public function getScript($group = 'global')
    {
        if (!isset($this->__script[$group])) {
            return false;
        }
        $script = '';
        foreach ($this->__script[$group] as $uri => $v) {
            $script .= "<script type=\"text/javascript\" src=\"{$uri}\"></script>";
        }
        if ($this->__inlineScript) {
            $script .= "<script type=\"text/javascript\">{$this->__inlineScript}</script>";
        }
        return $script;
    }

    /**
     * 获取样式表字符串
     * @param string $group 
     */
    public function getStyle($group = 'global')
    {
        if (!isset($this->__style[$group])) {
            return false;
        }
        $style = '';
        foreach ($this->__style[$group] as $uri => $v){
            $style .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$uri}\">\n";
        }
        if ($this->__inlineStyle) {
            $style .= "<style type=\"text/css\">{$this->__inlineStyle}</style>";
        }
        return $style;
    }

}
