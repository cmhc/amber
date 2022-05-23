<?php
/**
 * view 接口 
 */
namespace amber\contracts;

interface View
{
    /**
     * 设置模板路径或者模板类型
     * @param string $path
     */
    public function setTemplate(ViewTemplate $template);

    /**
     * 输出需要展示的内容
     */
    public function render($data);

    /**
     * 输出错误的页面 
     */
    public function renderError($data);
}