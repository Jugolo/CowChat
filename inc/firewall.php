<?php
namespace inc\firewall;

use inc\file\Files;
use inc\database\Database;

// the chat is written as data container. But not the firewall
class FireWall{
	private static $ip = []; // contains all ip there is temporary banned.
	public static function init(){
		self::garbage();
		self::load();
	}
	public static function getBans(){
		if(self::garbage() != 0){
			self::load();
		}
		
		return self::$ip;
	}
	public static function ban($expire, $ip = null){
		Database::getInstance()->insert("ip_ban", [
				'ip' => $ip == null ? ip() : $ip,
				'admin_id' => 0,
				'expired' => (string)$expire
		]);
	}
	public static function getInfoBan($ip){
		$query = Database::getInstance()->query("SELECT `ip`,`expired` FROM " . table("ip_ban") . " WHERE `ip`=" . Database::qlean($ip));
		$row = $query->fetch();
		$query->free();
		
		return $row;
	}
	public static function isBan(){
		if(self::garbage() != 0){
			self::load();
		}
		
		// control the white list an if this ip is in it dont return true but false
		if(in_array(ip(), self::getWhiteList()))
			return false;
		
		return in_array(ip(), self::$ip);
	}
	public static function getBlacklist(){
		if(Files::exists("include/firewall/blacklist.txt")){
			return remove_white_space(explode("\r\n", Files::context("include/firewall/blacklist.txt")));
		}
		return [];
	}
	public static function getWhiteList(){
		$dir = "include/firewall/whitelist.txt";
		if(Files::exists($dir)){
			return remove_white_space(explode("\r\n", Files::context($dir)));
		}
		return [];
	}
	public static function isBlacklist($ip){
		if(in_array($ip, self::getWhiteList())){
			return false; // this ip can never be baned from here or in channels
		}
		
		return in_array($ip, self::getBlacklist());
	}
	private static function garbage(){
		return Database::getInstance()->query("DELETE FROM " . table("ip_ban") . " WHERE `expired`<'" . time() . "'")->free();
	}
	private static function load(){
		self::$ip = [];
		$query = Database::getInstance()->query("SELECT `id`, `ip` FROM " . table("ip_ban"));
		while($row = $query->fetch()){
			self::$ip[$row["id"]] = $row["ip"];
		}
		$query->free();
	}
}

/**
 * This function remove white item in array
 * @param array $array the array you want to remove white space
 * @return array a array wdith no white space
 */
function remove_white_space(array $array){
	$return = [];
	foreach ($array as $value){
		if(empty($value) || !trim($value))
			continue;
			$return[] = $value;
	}
	return $return;
}
FireWall::init();