<?php
/**
 * autoload 
 * you can load this file if you do not use composer
 */
spl_autoload_register(function($class){
	$file = dirname(__DIR__) . '/' . str_replace('\\','/',$class) . '.php';
	if (file_exists($file)) {
		require_once $file;
	}
});