<?php
/**
 * 注册自动加载
 */
spl_autoload_register(function($class){
    $file = dirname(__DIR__) . '/' . str_replace('\\','/',$class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});