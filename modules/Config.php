<?php
/**
 * simple config
 */
namespace amber\modules;

class Config
{

	private static $config;

	/**
	 * get config
	 * @param  string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		if( isset(self::$config[$key]) ){
			return self::$config[$key];
		}else{
			return false;
		}
	}


	/**
	 * add config
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($key, $value)
	{
		if( !isset(self::$config[$key]) ){
			self::$config[$key] = $value;
		}else{
			throw new \Exception("config $key is exists", 1);
		}
	}

}
?>