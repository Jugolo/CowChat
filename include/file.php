<?php
class Files{
	public static function exists($name){
		return file_exists(getDir() . $name);
	}
	public static function context($name){
		return file_get_contents(getDir() . $name, false);
	}
	public static function isFile($name){
		return is_file(getDir() . $name);
	}
	public static function create($name, $context){
		$fopen = fopen(getDir().$name, "w+");
		fwrite($fopen, $context, strlen($context));
		fclose($fopen);
	}
}
class Dirs{
	public static function isDir($name){
		return is_dir(getDir() . $name);
	}
}
function DIR_SEP(){
	return Server::is_cli() ? DIRECTORY_SEPARATOR : "/";
}
function getDir(){
	$sep = DIR_SEP();
	$current_dir = str_replace(array(
			"\\",
			"/"
	), $sep, dirname($_SERVER["SCRIPT_FILENAME"]) . "\\");
	$chat_path = str_replace(array(
			"\\",
			"/"
	), $sep, CHAT_PATH);
	if($current_dir == $chat_path){
		if(preg_match("/^([a-zA-Z]):/", $chat_path)){
			if(Server::is_cli())
				return $chat_path;
			else
				return "";
		}
		return "." . $sep;
	}
	
	if(($pos = strpos($chat_path, $current_dir)) !== false){
		return $dir = substr(CHAT_PATH, $pos);
	}else{
		// exit(CHAT_PATH."|".$current_dir);
		$chat_path = explode($sep, $chat_path);
		$current_dir = explode($sep, $current_dir);
		if(count($current_dir) < count($chat_path)){
			throw new Exception("A chat file must be in chat system: " . implode(DIRECTORY_SEPARATOR, $current_dir));
		}
		
		$path = "";
		
		for($i = 0;$i < count($chat_path);$i++){
			if($current_dir[$i] == $chat_path[$i]){
				continue; // noo need to proccess this one
			}
			
			$path .= "../";
		}
		
		return $path;
	}
}
