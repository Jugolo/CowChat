<?php
class AutoLoader{
	/**
	 * A buffer to cache all part to file to avoid multi include
	 * @var array a buffer to parts
	 */
	private static $buffer = [];
	
	public static function set(){
		spl_autoload_register(array(
				__CLASS__,
				"load"
		));
	}
	private static function load($class){
		if(strpos($class, "\\") === false){
			return;
		}
		$part = str_replace("\\", "/", substr($class, 0, strrpos($class, "\\"))).".php";
		if($part == ".php"){
			exit("CowScript use namespace on class: ".$class);
		}
		self::loadOnce($part);
	}
	
	public static function loadOnce($path){
		if(!in_array($path, self::$buffer)){
			self::$buffer[] = $path;
			include $path;
		}
	}
}
