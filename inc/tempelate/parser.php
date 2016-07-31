<?php
namespace inc\tempelate\parser;

use inc\tempelate\string\TempelateString;
use inc\error\HeigLevelError;

class StringParser{
	private $string;
	private $pointer;
	private $line = 1;
	
	public function __construct(string $string){
		$this->string = new TempelateString($string);
	}
	
	public function getReader() : TempelateString{
		return $this->string;
	}
	
	public function context(){
		if(($char = $this->string->current()) === -1){
			return ["EOF", null];
		}
		
		if($char == 32){
			$this->string->next();
			return $this->context();
		}
		
		switch ($char){
			case 34:
			case 39:
				return ["string", $this->get_string($char)];
			case 40:
				$this->string->next();
				return ["left_bue", "("];
			case 41:
				$this->string->next();
				return ["right_bue", ")"];
			case 44:
				$this->string->next();
				return ["comma", ","];
			case 46:
				$this->string->next();
				return ["punktum", "."];
			case 91:
				$this->string->next();
				return ["left_bue", "("];
			case 93:
				$this->string->next();
				return ["right_bue", ")"];
		}
		
		if(($char >= 65 && $char <= 90 || $char >= 97 && $char <= 122) || $char == 95){
			$buffer = chr($char);
			while((($char = $this->string->next()) >= 65 && $char <= 90 || $char >= 97 && $char <= 122) || $char == 95){
				$buffer .= chr($char);
			}
			
			return ["variabel", $buffer];
		}elseif($char >= 48 && $char <= 57){
			$buffer = $this->get_int(chr($char));
			if($this->string->current() == 46){
				$this->string->next();
				$buffer = $this->get_int($buffer.".");
			}
			
			return ["int", $buffer];
		}
		
		throw new HeigLevelError("Unknown char detected in tempelate file", chr($char)."[".$char."]");
	}
	
	private function get_int(string $buffer) : string{
		while(($char = $this->string->next()) >= 48 && $char <= 57){
			$buffer .= chr($char);
		}
		
		return $buffer;
	}
	
	private function get_string(int $end){
		$buffer = "";
		while(($char = $this->string->next()) != -1 && $char != $end){
			$buffer .= chr($char);
		}
		
		if($char != $end){
			throw new HeigLevelError("Missing ".chr($char)." ind string!");
		}
		
		$this->string->next();
		return $buffer;
	}
}