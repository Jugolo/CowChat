<?php
namespace inc\module;

use inc\file\Files;
use inc\messageparser\MessageParser;
use inc\error\LowLevelError;
use inc\user\data\UserData;

class Module{
	private static $loaded = [];
	
	public static function handleRequest(string $str, UserData $user){
		$message = new MessageParser($str);
		if(!self::exists($message->command()) || self::load(strtolower($message->command()))){
			throw new LowLevelError("unknown command: ".$message->command());
		}
		
		$name = strtolower($message->command())."_command";
		$name($message, $user);
	}
	
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
