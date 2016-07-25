<?php
class HeadCache{
	public static $cookie;
}
function setSocketCookie($cookie){
	HeadCache::$cookie = []; // empty the value from the last connection
	foreach(explode(";", $cookie) as $one){
		list($key, $value) = explode("=", $one);
		HeadCache::$cookie[$key] = $value;
	}
}
function make_cookie($name, $value){
	setcookie($name, $value, time() + 60 * 60 * 24);
}
function cookieDestroy($name){
	setcookie($name, "", time() - 9999);
}
function cookie($name){
	if(Server::is_cli())
		$use = HeadCache::$cookie;
	else
		$use = $_COOKIE;
	
	if(empty($use[$name]) || !trim($use[$name]))
		return null;
	return $use[$name];
}
function post($name){
	if(empty($_POST[$name]) || !trim($_POST[$name]))
		return null;
	return $_POST[$name];
}
function get($name){
	if(empty($_GET[$name]) || !trim($_GET[$name]))
		return null;
	return $_GET[$name];
}
