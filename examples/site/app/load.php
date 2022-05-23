<?php
namespace app;
use amber\modules\Hub;

ini_set('display_errors', "on");
require dirname(__DIR__) . "/amber/autoload.php";

// 数据库操作
Hub::bind('DB', function(){
    return new \amber\modules\DB(\amber\modules\Config::getf('wwwroot\config\db'));
});

// 载入路由
foreach (glob(__DIR__ . '/router/*.php') as $route) {
    require $route;
}