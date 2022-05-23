<?php
namespace app;
use amber\modules\Hub;

ini_set('display_errors', "on");
require dirname(__DIR__) . "/amber/autoload.php";

// 绑定别名
Hub::bind('Home', function(){
    return new controller\Home();
});

// 载入路由
foreach (glob(__DIR__ . '/router/*.php') as $route) {
    require $route;
}