<?php

class Html{
	private static $items = ['error' => []];
	public static function error($msg){
		self::$items['error'][] = $msg;
	}
	
	public static function getAguments(){
		return self::$items;
	}
}