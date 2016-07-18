<?php
namespace inc\head;

use inc\system\System;
use inc\head\Head;

class Head{
	public static $cookie;
	
	static function setSocketCookie($cookie){
		HeadCache::$cookie = []; // empty the value from the last connection
		foreach(explode(";", $cookie) as $one){
			list($key, $value) = explode("=", $one);
			self::$cookie[$key] = $value;
		}
	}
	static function make_cookie($name, $value){
		setcookie($name, $value, time() + 60 * 60 * 24);
		$_COOKIE[$name] = $value;
	}
	static function cookieDestroy($name){
		setcookie($name, "", time() - 9999);
		unset($_COOKIE[$name]);
	}
	static function cookie($name){
		if(System::is_cli())
			$use = Head::$cookie;
			else
				$use = $_COOKIE;
	
				if(empty($use[$name]) || !trim($use[$name]))
					return null;
					return $use[$name];
	}
	static function post($name){
		if(empty($_POST[$name]) || !trim($_POST[$name]))
			return null;
			return $_POST[$name];
	}
	static function get($name){
		if(empty($_GET[$name]) || !trim($_GET[$name]))
			return null;
			return $_GET[$name];
	}
	static function is_ssl(){
		return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
	}
}