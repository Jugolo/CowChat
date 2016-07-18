<?php
namespace inc\driver\tempelate\tempelate_language;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;
use inc\error\HeigLevelError;

class TempelateDriver implements TempelateInterface{
	public function render(string $context, Render $render) : string{
		if(preg_match("/^\|(.*?)\|(.*?)$/", $context, $preg)){
			echo "<pre>";
			print_r($preg);
			exit("</pre>");
		}
		
		throw new HeigLevelError("Language syntrack contain error", $context);
	}
}