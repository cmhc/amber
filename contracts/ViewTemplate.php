<?php
namespace amber\contracts;

interface ViewTemplate
{    
    /**
     * 添加模板成分 
     */
    public function addPart($path);

    /**
     * 输出
     */
    public function render($data);
}