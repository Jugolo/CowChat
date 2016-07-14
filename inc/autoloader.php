<?php
class AutoLoader{
	/**
	 * A buffer to cache all part to file to avoid multi include
	 * @var array a buffer to parts
	 */
	private static $buffer = [];
	
	public static function set_path($part) : bool{
		if(!defined("CHAT_PATH")){
			define("CHAT_PATH", $part);
			set_include_path(CHAT_PATH);
			return true;
		}else{
			return false;
		}
	}
	
	public static function set(){
		spl_autoload_register(array(
				__CLASS__,
				"load"
		));
	}
	private static function load($class){
		if(strpos($class, "Twig") === 0){
			self::LoadTwig($class);
			return;
		}
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
	
	private static function LoadTwig($name){
		$dir = "inc/twig/".str_replace("_", "/", substr(explode("\\", $name)[0], 5)).".php";
		if(inc\file\Files::exists($dir)){
			self::loadOnce($dir);
		}
	}
}
