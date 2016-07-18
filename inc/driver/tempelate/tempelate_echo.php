<?php
namespace inc\driver\tempelate\tempelate_echo;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;

class TempelateDriver implements TempelateInterface{
	function allow_php_tag() : bool{
		return true;
	}
	
	public function render(string $context, Render $render) : string{
		return "echo \$this->type->control_string(".$render->parseString($context).");";
	}
}