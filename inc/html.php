<?php
namespace inc\html;

class Html{
	private static $items = [
			'error' => [],
			'okay'  => []
	];
	public static function error($msg){
		self::$items['error'][] = $msg;
	}
	public static function okay(string $msg){
		self::$items['okay'][] = $msg;
	}
	public static function getAguments(){
		return self::$items;
	}
}