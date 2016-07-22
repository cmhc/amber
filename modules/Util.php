<?php
namespace cmhc\amber\modules;
class Util{
	public static function getSiteUrl()
	{
		return (($_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . stripslashes(dirname($_SERVER['PHP_SELF'])) . '/';
	}
}