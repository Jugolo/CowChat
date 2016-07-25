<?php
namespace inc\driver\tempelate\tempelate_for;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;
use inc\error\HeigLevelError;

class TempelateDriver implements TempelateInterface{
	function allow_php_tag() : bool{
		return true;
	}
	
	public function render(string $context, Render $render) : string{
		$parts = explode(" ", $context);
		$return = "foreach(\$this->type->control_array(".$render->parseString($parts[0]).") AS";
		if($parts[1] != "AS"){
			throw new HeigLevelError("After array in foreach there must be a 'AS'", $parts[1]);
		}

		$return .= " \$".$parts[2];
		$extra  = "\$this->database->put('".$parts[2]."', \$".$parts[2].");";
		
		if(count($return) == 5){
			if($return[3] != ":"){
				throw new HeigLevelError("Unknown token ".$return[3], "Excepted ':'");
			}
			$return .= " => \$".$parts[4];
			$extra .= "\r\n\$this->database->put('".$parts[4]."', \$".$parts[4].");";
		}
		
		return $return."){\r\n".$extra;
	}
}