<?php
namespace cmhc\amber\modules;

class View
{
    protected $viewPath;

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
