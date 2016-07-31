<?php
namespace inc\driver\tempelate\tempelate_if;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;

class TempelateDriver implements TempelateInterface{
	function allow_php_tag() : bool{
		return true;
	}
	
	public function render(string $context, array $options) : string{
		$bool = Render::parseString($context);
		return "if(".$bool."){";
	}
}