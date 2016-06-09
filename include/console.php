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
}
