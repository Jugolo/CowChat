<?php
namespace inc\module;

use inc\file\Files;

class Module{
	private static $loaded = [];
	
	public static function load($load){
		if(!empty(self::$loaded[$load])){
			return false;
		}
		if(self::exists($load)){
			self::$loaded[$load] = true;
			include self::url($load);
			return true;
		}else{
			return false;
		}
	}
	
	public static function exists($name) : bool{
		return Files::exists(self::url($name));
	}
	
	private static function url($name){
		return "inc/module/" . $name . "/" . $name . ".php";
	}
}
