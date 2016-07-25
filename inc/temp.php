<?php
namespace inc\temp;

use inc\file\Files;
use inc\error\HeigLevelError;

class Temp{
	public static function exists(string $name, string $prefix = ""){
		return Files::exists(self::getTempName($name, $prefix));
	}
	
	public static function get(string $name, string $prefix = ""){
		if(self::exists($name, $prefix)){
			return Files::context(self::getTempName($name, $prefix));
		}
		
		throw new HeigLevelError("Unknown temp ".$name.($prefix === "" ? "" : " width prefix".$prefix), self::getTempName($name, $prefix));
	}
	
	public static function create(string $name, string $context, string $prefix = ""){
		Files::create(self::getTempName($name, $prefix), $context);
	}
	
	public static function remove(string $name, string $prefix = "") : bool{
		return Files::remove(self::getTempName($name, $prefix));
	}
	
	private static function getTempName(string $name, string $prefix){
		return "inc/temp/".$prefix.($prefix === "" ? "" : "_").md5($name).".tmp";
	}
}