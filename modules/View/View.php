<?php
namespace amber\modules\View;
use amber\contracts\ViewTemplate;

class View
{
    /**
     * 设置模板路径或者模板类型
     * @param string $path
     */
    public function setTemplate(ViewTemplate $template)
    {
        $this->ViewTemplate = $template;
    }

    /**
     * 输出需要展示的内容
     */
    public function render($data)
    {
        $this->ViewTemplate->render($data);
    }

    /**
     * 输出错误的页面 
     */
    public function renderError($data)
    {
        header("HTTP/1.1 404 Not Found");
        $this->ViewTemplate->renderError($data);
    }
}