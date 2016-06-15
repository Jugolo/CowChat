<?php
class Setting{
	private static $settings = [];
	public static function init(){
		$query = Database::query("SELECT `key`, `value` FROM " . table("setting"));
		while($row = $query->fetch())
			self::$settings[$row['key']] = $row["value"];
	}
	public static function exists($name){
		return array_key_exists($name, self::$settings);
	}
	public static function get($name){
		return self::$settings[$name];
	}
        public static function push($key, $value){
                if(!self::exists($key)){
                    Database::insert("setting", [
                        "key"   : $key,
                        "value" : $value
                    ]);
                }else{
                    Database::query("UPDATE ".table("setting")." SET `value`=".Database::qlean($value)." WHERE `key`=".Database::qlean($key));
                }
                self::settings[$key] = $value;
        }
        public static function delete($key){
                if(self::exists($key)){
                      Database::query("DELETE FROM ".table("setting")." WHERE `key`=".Database::qlean($key));
                      unset(self::settings[$key]);
                }
        }
}
