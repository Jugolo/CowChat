<?php
namespace inc\driver\tempelate\tempelate_if;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;

class TempelateDriver implements TempelateInterface{
	public function render(string $context, Render $render) : string{
		$bool = $render->parseString($context);
		return "if(".$bool."){";
	}
}