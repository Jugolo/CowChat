<?php
namespace inc\smylie;

use inc\temp\Temp;
use inc\database\Database;

class Smylie{
	private static $buffer;
	
	public static function init(){
		if(Temp::exists("smylie")){
			self::$buffer = json_decode(Temp::get("smylie"), true);
		}else{
			self::$buffer = [];
			$query = Database::getInstance()->query("SELECT `url`, `title`, `tag` FROM ".table("smylie"));
			while($row = $query->fetch()){
				self::$buffer[] = $row;
			}
			
			Temp::create("smylie", json_encode(self::$buffer));
		}
	}
	
	public static function getList() : array{
		return self::$buffer;
	}
}

Smylie::init();