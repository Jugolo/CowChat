<?php
namespace inc\image;

use inc\temp\Temp;
use inc\database\Database;
use inc\error\HeigLevelError;
use inc\file\Files;

class Image{
	public static function base64_encode($url){
		if(!Files::exists($url)){
			throw new HeigLevelError("Unknown path", $url);
		}
		$info = pathinfo($url);
		if(!self::isImage($info["extension"])){
			throw new HeigLevelError("The file is not a image", $info["extension"]);
		}
		
		return "data:".self::getMimeType($info["extension"]).";base64,".base64_encode(Files::context($url));
	}
	
	private static $list = [];
	
	public static function isImage(string $type) : bool{
		return array_key_exists($type, self::$list);
	}
	
	public static function getMimeType(string $type) : string{
		 if(self::isImage($type)){
		 	return self::$list[$type]["mime"];
		 }
		 
		 throw new HeigLevelError("Unknown mime type", $type);
	}
	
	public static function getList(){
		if(Temp::exists("image_list")){
			self::$list = json_decode(Temp::get("image_list"), true);
		}else{
			self::$list = [];
			$database = Database::getInstance();
			$query = $database->query("SELECT * FROM ".table("filetype")." WHERE `type`='image'");
			while($row = $query->fetch()){
				self::$list[$row["name"]] = $row;
			}
			
			Temp::create("image_list", json_encode(self::$list));
		}
	}
}

Image::getList();