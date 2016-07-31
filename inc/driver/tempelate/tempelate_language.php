<?php
namespace inc\driver\tempelate\tempelate_language;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;
use inc\error\HeigLevelError;

class TempelateDriver implements TempelateInterface{
	function allow_php_tag() : bool{
		return true;
	}
	
	public function render(string $context, array $options) : string{
		if(preg_match("/^\|(.*?)\| ?(.*?)$/", $context, $preg)){
			$arg = [];
			foreach(explode(",", $preg[2]) as $key){
				if($key != ""){
					$arg[] = Render::parseString($key);
				}
			}
			
			return "echo sprintf(call_user_func_array(['inc\\language\\Language', 'get'], ['".$preg[1]."'])".(count($arg) != 0 ? ", ".implode(", ", $arg) : "").");";
		}
		
		throw new HeigLevelError("Language syntrack contain error", $context);
	}
}