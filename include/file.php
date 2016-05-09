<?php

class Files{
  public static function exists($name){
     return @fopen($name, "r", true) == true;
  }

  public static function context($name){
    return file_get_contents($name, true);
  }

  public static function isFile($name){
     return @file($name, FILE_USE_INCLUDE_PATH) != false;
  }
}

class Dirs{
   public static function isDir($name){
   	  return is_dir(getDir().$name);
   }
}

function getDir(){
	static $dir = null;
	
	if($dir !== null){
		return $dir;
	}
	
	//wee get the current dir and chat dir :)
	$current_dir = str_replace("/", "\\", dirname($_SERVER["SCRIPT_FILENAME"])."\\");
	if($current_dir == CHAT_PATH){
		return $dir = "";
	}
	
	if(($pos = strpos(CHAT_PATH, $current_dir)) !== false){
		return $dir = substr(CHAT_PATH, $pos);
	}else{
		//exit(CHAT_PATH."|".$current_dir);
		$chat_path = explode("\\", CHAT_PATH);
		$current_dir = explode("\\", $current_dir);
		if(count($current_dir) < count($chat_path)){
			throw new Exception("A chat file must be in chat system: ".implode("\\", $current_dir));
		}
		
		$path = "";
		
		for($i=0;$i<count($chat_path);$i++){
			if($current_dir[$i] == $chat_path[$i]){
				continue;//noo need to proccess this one
			}
			
			$path .= "../"; 
		}
		
		return $path;
	}
}
