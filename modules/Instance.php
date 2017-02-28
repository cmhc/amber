<?php
namespace amber\modules;
class Instance
{
	protected static $singleton = array();

	/**
	 * get singleton
	 */
	public static function singleton()
	{
		$called = get_called_class();
		$args = func_get_args();
		$classHash = md5($called . json_encode($args));
		if (isset(self::$singleton[$classHash])) {
			return self::$singleton[$classHash];
		}
		if (!empty($args)) {
			$reflection = new \ReflectionClass($called);
			return self::$singleton[$classHash] = $reflection->newInstanceArgs($args);
		} else {
			return self::$singleton[$classHash] = new $called();
		}

	}

	/**
	 * get factory
	 */
	public static function factory()
	{
		$called = get_called_class();
		$args = func_get_args();
		if (!empty($args)) {
			$reflection = new \ReflectionClass($called);
			return $reflection->newInstanceArgs($args);
		} else {
			return new $called();
		}
	}

}