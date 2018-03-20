<?php
/**
 * 展示静态页
 */
namespace amber\modules;

class View
{
    protected $viewPath = null;

    protected $class = array();

    protected $style = array();

    protected $script = array();

    public function __construct($viewPath)
    {
        $this->viewPath = $viewPath;
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
        if(isset($this->class[$method])){
            $class = $this->class[$method];
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
        $this->class[$method] = $class;
    }

    /**
     * 载入模板文件
     * @param $view
     */
    public function display($view)
    {
        require $this->viewPath . '/' . $view;
    }

    /**
     * 获取模板目录
     * @return
     */
    public function getPath()
    {
        return $this->viewPath;
    }

    /**
     * add script
     */
    public function addScript($uri, $group = 'global')
    {
        $this->script[$group][$uri] = 1;
    }

    /**
     * add style path
     */
    public function addStyle($uri, $group = 'global')
    {
        $this->style[$group][$uri] = 1;
    }

    /**
     * get script
     * @param  string $group 
     */
    public function getScript($group = 'global')
    {
        if (!isset($this->script[$group])) {
            return false;
        }
        $script = '';
        foreach ($this->script[$group] as $uri => $v) {
            $script .= "<script type=\"text/javascript\" src=\"{$uri}\"></script>";
        }
        return $script;
    }

    /**
     * get style
     * @param  string $group 
     */
    public function getStyle($group = 'global')
    {
        if (!isset($this->style[$group])) {
            return false;
        }
        $style = '';
        foreach ($this->style[$group] as $uri => $v){
            $style .= "<link rel=\"stylesheet\" href=\"{$uri}\">\n";
        }
        return $style;
    }

}
