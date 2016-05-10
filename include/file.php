<?php

class Files{
  public static function exists($name){
     return file_exists(getDir().$name);
  }

  public static function context($name){
    return file_get_contents(getDir().$name);
  }

  public static function isFile($name){
     return is_file(getDir().$name);
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
	//exit(dirname($_SERVER["SCRIPT_FILENAME"])."|".CHAT_PATH);
	//wee get the current dir and chat dir :)
	$current_dir = str_replace("/", "\\", dirname($_SERVER["SCRIPT_FILENAME"])."\\");
        $chat_path   = str_replace("/", "\\", CHAT_PATH);
	if($current_dir == $chat_path){
		return $dir = "";
	}
	
	if(($pos = strpos($chat_path, $current_dir)) !== false){
		return $dir = substr(CHAT_PATH, $pos);
	}else{
		//exit(CHAT_PATH."|".$current_dir);
		$chat_path = explode("\\", $chat_path);
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
		
		return $dir = $path;
	}
}