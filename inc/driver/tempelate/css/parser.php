<?php

namespace inc\driver\tempelate\css\parser;

use inc\tempelate\string\TempelateString;
use inc\file\Files;
use inc\error\HeigLevelError;
use inc\driver\tempelate\css\tokenize\CssTokenize;

class CssParser{
	private static $buffer = [];
	public static function render(string $url, array $option){
		self::reseat();
		self::parse(new TempelateString(Files::context($url)), $option);
	}
	public static function reseat(){
		self::$buffer = [];
	}
	private static function parse(TempelateString $string, array $option){
		while($string->current() != -1){
			self::eatChar($string);
			switch($string->current()){
				case 64:
					$string->next();
					self::handleSnabela($string, $option);
				break;
				default:
					throw new HeigLevelError("Unknown token in css file: ".chr($string->current()));
			}
		}
	}
	private static function handleSnabela(TempelateString $string, array $option){
		$key = self::getVariabel($string);
		switch($key){
			case "import":
				self::handleImport($string);
			break;
		}
	}
	
	private static function handleImport(TempelateString $string){
		self::eatChar($string);
		$data = self::getVariabel($string);
		switch($data){
			case "url":
				if($string->current() != 40){
					throw new HeigLevelError("Missing ( after @import url. Got: ".chr($string->current()));
				}
				$string->next();
				$context = CssTokenize::get(self::getTo($string, 41, 40));
			break;
			default:
				throw new HeigLevelError("Unknown token after @import: ".$data);
		}
	}
	
	private static function getVariabel(TempelateString $string) : string{
		if(!self::isVariabelChar($string->current())){
			throw new HeigLevelError("Unknown variabel char: ".chr($string->current()));
		}
		
		$buffer = chr($string->current());
		
		while(self::isVariabelChar(($char = $string->next()))){
			$buffer .= chr($char);
		}
		
		return $buffer;
	}
	
	private static function isVariabelChar(int $char){
		return ($char >= 65 && $char <= 90 || $char >= 97 && $char <= 122) || $char == 45 || $char == 95;
	}
	
	private static function eatChar(TempelateString $string){
		if($string->current() > 32){
			return;
		}
		while($string->next() <= 32)
			;
	}
	
	private static function getTo(TempelateString $string, int $to, int $countFrom = -2) : string{
		$count = 1;
		$buffer = chr($string->current());
		while(($char = $string->next()) != -1){
			if($countFrom != -2 && $char == $countFrom){
				$count++;
			}elseif($char == $to){
				$count--;
				if($count === 0){
					break;
				}
			}
			
			$buffer .= chr($char);
		}
		
		if($count != 0){
			throw new HeigLevelError("Missing ".chr($to));
		}
		exit($buffer);
		return $buffer;
	}
}