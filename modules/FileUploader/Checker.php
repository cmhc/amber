<?php
// 文件上传检查工具
namespace amber\modules\FileUploader;

interface Checker
{
    public function isValid($file);
}