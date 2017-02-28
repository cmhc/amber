<?php
/**
 * simple config
 */
namespace amber\modules;

class Config
{

	private static $config;

	protected static $configFile;

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

	/**
	 * get config file
	 * @param $key
	 */
	public static function getf($key)
	{
		if (isset(self::$configFile[$key]) && file_exists(self::$configFile[$key])) {
			return require self::$configFile[$key];
		}

		$dirname = str_replace('\\', '/', __DIR__);
		$namespace = str_replace('\\', '/', __NAMESPACE__);
		$base = str_replace($namespace, '', $dirname);
		self::$configFile[$key] = $base . '/' . str_replace('\\', '/', $key . '.php');
		if (file_exists(self::$configFile[$key])) {
			return require self::$configFile[$key];
		}
		return false;
	}

}
?>