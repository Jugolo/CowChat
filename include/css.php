<?php

class Css{
	public static function parse($string){
		return self::begin(new CssReader($string), []);
	}
	
	private static function begin(CssReader $reader, $variabels){
		
	}
}

class CssReader{
	private $int = 0;
	private $char = [];
	
	public function __construct($string){
		$this->char = $string;
	}
}