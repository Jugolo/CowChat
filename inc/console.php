<?php
class Console{
	public static function write($msg){
		echo $msg;
	}
	public static function writeLine($message){
		self::write($message . "\r\n");
	}
	public static function readLine(){
		return rtrim(fgets(STDIN));
	}
	public static function title($title){
		cli_set_process_title($title);
	}
}
