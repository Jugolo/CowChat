<?php
namespace inc\driver\tempelate\tempelate_set;

use inc\interfaces\tempelate\TempelateInterface;
use inc\error\HeigLevelError;
use inc\tempelate\render\Render;

class TempelateDriver implements TempelateInterface{
	function allow_php_tag() : bool{
		return true;
	}
	
	public function render(string $context, array $options) : string{
		$pointer = strpos($context, " ");
		if($pointer === false){
			throw new HeigLevelError("SET syntrack is not followed");
		}
		
		return "\$this->database->put('".substr($context, 0, $pointer)."', ".Render::parseString(substr($context, $pointer+1)).");";
	}
}