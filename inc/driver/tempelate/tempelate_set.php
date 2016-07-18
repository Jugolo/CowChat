<?php
namespace inc\driver\tempelate\tempelate_set;

use inc\interfaces\tempelate\TempelateInterface;
use inc\error\HeigLevelError;
use inc\tempelate\render\Render;

class TempelateDriver implements TempelateInterface{
	public function render(string $context, Render $render) : string{
		$pointer = strpos($context, " ");
		if($pointer === false){
			throw new HeigLevelError("SET syntrack is not followed");
		}
		
		return "\$this->database()->put('".substr($context, 0, $pointer)."', ".$render->parseString(substr($context, $pointer+1, $start)).");";
	}
}