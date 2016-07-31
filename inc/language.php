<?php
namespace inc\language;

use inc\head\Head;
use inc\file\Files;
use inc\error\HeigLevelError;
use inc\logging\Logging;
use inc\system\System;
use inc\temp\Temp;

class Language{
	private static $language = null;
	public static function load(){
		if(Head::cookie("language") && Files::exists("inc/language/" . Head::cookie("language") . ".lang")){
			self::$language = self::parse_file("inc/language/" . Head::cookie("language") . ".lang", Head::cookie("language"));
		}else{
			if(strpos($_SERVER["HTTP_ACCEPT_LANGUAGE"], ",")){
				list($language, $data) = explode(";", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
				foreach(explode(",", $language) as $lang){
					if(strpos("-", $lang)){
						$lang = substr($lang, 0, strpos("-", $lang));
					}
					if(Files::exists("inc/language/" . $lang . ".lang")){
						self::$language = self::parse_file("inc/language/" . $lang . ".lang", $lang);
						return; // stop here
					}
				}
			}else{
				if(Files::exists("inc/language/" . $_SERVER["HTTP_ACCEPT_LANGUAGE"] . ".lang")){
					self::$language = self::parse_file("inc/language/" . $_SERVER["HTTP_ACCEPT_LANGUAGE"] . ".lang", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
					return;
				}else{
					self::$language = [];
				}
			}
		}
	}
	public static function show_page(){
		if(self::$language === null){
			self::load();
		}
		if(!Head::get("respons")){
			exit("error: You cant not request language list widthout 'respons' agument");
		}
		header('Content-Type: application/json');
		switch(Head::get("respons")){
			case "json":
				exit(json_encode(self::$language));
			default:
				exit("error: unknown respons type: "+Head::get("respons"));
		}
	}
	public static function get($text){
		if(self::$language === null && !System::is_cli()){
			self::load();
		}
		if(array_key_exists($text, self::$language)){
			return self::$language[$text];
		}
		Logging::getInstance("missing_language")->push("Missing language: ".$text);
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
	
	private static function parse_file(string $dir, string $name) : array{
		if(Temp::exists($name, "lang")){
			if(Temp::changeTime($name, "lang") > Files::changeTime($dir)){
				return json_decode(Temp::get($name, "lang"), true);
			}else{
				Temp::remove($name, "lang");
			}
		}
		$file = Files::context($dir);
		$buffer = [];
		while(strlen($file) !== 0){
			if(strpos($file, "ADD ")===0){
				$file = substr($file, 4);
				$text = substr($file, 0, (($pos = strpos($file, "\r\n")) !== false ? $pos : strlen($file)));
				if(strpos($file, "=>") === false){
					throw new HeigLevelError("Missing =>", $text);
				}
				list($key, $value) = explode("=>", $text);
				$buffer[$key] = $value;
				$file = substr($file, strlen($text)+2);
			}else{
				$line = substr($file, 0, ($pos = strpos($file, "\r\n") !== false) ? $pos : strlen($file));
				if(trim($line) === ""){
					$file = substr($file, strlen($line)+1);
				}else{
					throw new HeigLevelError("Unknown line in language file: ".$line, $dir);
				}
			}
		}
		
		Temp::create($name, json_encode($buffer), "lang");
		
		return $buffer;
	}
}
