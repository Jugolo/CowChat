<?php
class AutoLoader{
	public static function set(){
		spl_autoload_register(array(
				__CLASS__,
				"load"
		));
	}
	private static function load($class){
		if(strpos($class, "Twig") === 0){
			self::LoadTwig($class);
		}
	}
	private static function LoadTwig($name){
		// remove Twig_ becuse no need to use it and replace all _ to /
		$dir = "include/twig/" . str_replace("_", "/", substr($name, 5)) . ".php";
		if(Files::exists($dir)){
			include $dir;
		}
	}
}
