<?php
use amber\modules\Hub;
use amber\modules\DB;
use amber\modules\Config;
require dirname(__DIR__) . '/autoload.php';

Hub::bind('DB', function(){
    return new DB(Config::getf('amber\testcase\config\db'));
});