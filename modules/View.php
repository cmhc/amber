<?php
namespace cmhc\amber\modules;

class View
{
    protected $viewPath;

    protected $class;

    public function __construct($viewPath)
    {
        $this->viewPath = $viewPath;
    }

    /**
     * when visit a property which is not exist, this method will called
     */
    public function __get($name)
    {
        return;
    }

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
     * assign value to property
     * @param  string $var
     * @param  mixed $value
     * @return
     */
    public function assign($var, $value)
    {
        $this->$var = $value;
    }

    /**
     * add method array
     * use this method to define a relation which is method from class when the class called a not exits method
     */
    public function addMethod($method,$class)
    {
        $this->class[$method] = $class;
    }

    /**
     * display template
     * @param $view template absolute path
     * @param $loadmobile boolean default true 2015/06/25加上 默认会搜索手机模板
     */
    public function display($view)
    {
        require $this->viewPath . '/' . $view;
    }

    public function getPath()
    {
        return $this->viewPath;
    }

}
