<?php
namespace inc\driver\tempelate\tempelate_include;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;
use inc\error\HeigLevelError;
use inc\tempelate\tempelate\Tempelate;

class TempelateDriver implements TempelateInterface{
	function allow_php_tag() : bool{
		return false;
	}
	
	function render(string $context, Render $render) : string{
		//render dir if 'dir' option is set
		$option = $render->getTempeleate()->getOptionArray();
		$clean = explode(".", $context);
		if(array_key_exists("dir", $option)){
			$dir = $option["dir"];
			for($i=0;$i<count($clean);$i++){
				if(count($clean)-1 == $i){
					if(!file_exists($dir.$clean[$i].".inc")){
						throw new HeigLevelError("Missing the include tempelate file", $dir.$clean[$i].".inc");
					}
					$dir .= $clean[$i].".inc";
				}else{
					if(!is_dir($dir.$clean[$i])){
						throw new HeigLevelError("Missing tempelate dir", $dir.$clean[$i]);
					}
					$dir .= $clean[$i]."/";
				}
			}
			
			//let us trim the dir to avoid error
			$dir = substr($dir, strlen($option["dir"]));
		}else{
			$dir = explode("/", $clean).".inc";
		}
		
		$option["no_prefix"] = true;
		
		$templeate = new Tempelate($option);
		return $templeate->getCompiledSource($dir);
	}
}