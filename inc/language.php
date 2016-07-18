<?php
namespace inc\language;

use inc\head\Head;
use inc\file\Files;
use inc\error\HeigLevelError;

class Language{
	private static $language = [];
	private static $name = null;
	public static function load($name){
		if(Head::cookie("language") && Files::exists("inc/language/" . Head::cookie("language") . "/" . $name . ".php")){
			include "inc/language/" . Head::cookie("language") . "/" . $name . ".php";
			self::$name = Head::cookie("language");
			self::$language = $lang;
		}else{
			if(strpos($_SERVER["HTTP_ACCEPT_LANGUAGE"], ",")){
				list($language, $data) = explode(";", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
				foreach(explode(",", $language) as $lang){
					if(strpos("-", $lang)){
						$lang = substr($lang, 0, strpos("-", $lang));
					}
					if(Files::exists("inc/language/" . $lang . "/" . $name . ".php")){
						self::$name = $lang;
						include "inc/language/" . $lang . "/" . $name . ".php";
						self::$language = $lang;
						return; // stop here
					}
				}
			}else{
				if(Files::exists("inc/language/" . $_SERVER["HTTP_ACCEPT_LANGUAGE"] . "/" . $name . ".php")){
					include "inc/language/" . $_SERVER["HTTP_ACCEPT_LANGUAGE"] . "/" . $name . ".php";
					self::$language = $lang;
					self::$name = $lang;
					return;
				}
			}
		}
	}
	public static function get($text){
		if(array_key_exists($text, self::$language)){
			return self::$language[$text];
		}
		
		return $text;
	}
	
	public static function get_sprintf(){
		if(func_num_args() == 0){
			throw new HeigLevelError("get_sprintf can only take 1 or more aguments");
		}
		
		$arg = func_get_args();
		$arg[0] = self::get($arg[0]);
		return call_user_func_array("sprintf", $arg);
	}
	
	public static function getLanguageName(){
		return self::$name;
	}
}
