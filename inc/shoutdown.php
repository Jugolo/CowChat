<?php
namespace inc\shoutdown;

use inc\plugin\Plugin;

class ShoutDown{
	private static $buffer = [];
	
	static function init(){
		self::append(function(){
			Plugin::getInstance()->event("system.end", []);
		});
		register_shutdown_function(["inc\\shoutdown\\ShoutDown", "trigger"]);
	}
	
	static function append(callable $callback){
		self::$buffer[] = $callback;
	}
	
	public static function trigger(){
		foreach(self::$buffer as $callback){
			call_user_func_array($callback, []);
		}
	}
}