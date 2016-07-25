<?php
namespace inc\html;

use inc\tempelate\tempelate\Tempelate;

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
	public static function set_agument(Tempelate $tempelate){
		$tempelate->add_var_array(self::$items);
	}
}