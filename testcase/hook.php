<?php
require_once 'load.php';
use amber\modules\Hook;

Hook::add('s',function($arg){
	echo $arg . "first\n";
}, 10);

function a($arg, $c)
{
	echo $arg . $c . "aaa\n";
}

class a{
	function b($arg)
	{
		echo $arg . "bbbb\n";
	}
}
Hook::add('s', 'a', 10, 2);
Hook::add('s', array('a', 'b'), 10);
Hook::remove('s', 'a', 8);
$arg = 'args:';
$asd = 'ssd:';


Hook::apply('s', array($arg, $asd));