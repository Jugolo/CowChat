<?php
class Language{
	private static $language = [];
	private static $name = null;
	public static function load($name){
		if(cookie("language") && Files::exists("include/language/" . cookie("language") . "/" . $name . ".php")){
			include "include/language/" . cookie("language") . "/" . $name . ".php";
			self::$name = cookie("language");
			self::$language = $lang;
		}else{
			if(strpos($_SERVER["HTTP_ACCEPT_LANGUAGE"], ",")){
				list($language, $data) = explode(";", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
				foreach(explode(",", $language) as $lang){
					if(strpos("-", $lang)){
						$lang = substr($lang, 0, strpos("-", $lang));
					}
					if(Files::exists("include/language/" . $lang . "/" . $name . ".php")){
						self::$name = $lang;
						include "include/language/" . $lang . "/" . $name . ".php";
						self::$language = $lang;
						return; // stop here
					}
				}
			}else{
				if(Files::exists("include/language/" . $_SERVER["HTTP_ACCEPT_LANGUAGE"] . "/" . $name . ".php")){
					include "include/language/" . $_SERVER["HTTP_ACCEPT_LANGUAGE"] . "/" . $name . ".php";
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
	public static function getLanguageName(){
		return self::$name;
	}
}
