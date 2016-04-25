<?php
class Setting{
	private static $settings = [];
	
	public static function init(){
		$query = Database::query("SELECT `key`, `value` FROM ".table("setting"));
		while($row = $query->fetch())
			self::$settings[$row['key']] = $row["value"];
	}
	
	public static function exists($name){
		return array_key_exists($name, self::$settings);
	}
	
	public static function get($name){
		return self::$settings[$name];
	}
}