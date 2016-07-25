<?php
class Module{
	private static $loaded = [];
	public static function load($load){
		if(!empty(self::$loaded[$load])){
			return false;
		}
		if(Files::exists(self::url($load))){
			self::$loaded[$load] = true;
			include self::url($load);
			return true;
		}else{
			return false;
		}
	}
	private static function url($name){
		return "include/module/" . $name . "/" . $name . ".php";
	}
}
