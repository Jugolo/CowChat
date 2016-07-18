<?php
namespace inc\tempelate\parser;

use inc\tempelate\string\TempelateString;
use inc\error\HeigLevelError;

class StringParser{
	private $string;
	private $pointer;
	
	public function __construct(string $string){
		$this->string = new TempelateString($string);
	}
	
	public function context(){
		if(($char = $this->string->current()) === -1){
			return ["EOF", null];
		}
		
		if($char >= 65 && $char <= 90 || $char >= 97 && $char <= 122){
			$buffer = chr($char);
			while(($char = $this->string->next()) >= 65 && $char <= 90 || $char >= 97 && $char <= 122){
				$buffer .= chr($char);
			}
			
			return ["variabel", $buffer];
		}
		
		throw new HeigLevelError("Unknown char detected in tempelate file", chr($char));
	}
}