<?php
/**
 * 视图类
 * 载入前端页面
 */
namespace amber\modules;

class View
{
    protected $viewPath = null;

    protected $class = array();

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

}
