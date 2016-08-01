<?php
namespace inc\driver\tempelate\tempelate_css;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;
use inc\driver\tempelate\css\parser\CssParser;

class TempelateDriver implements TempelateInterface{
	public function allow_php_tag() : bool{
		return false;
	}
	
	public function render(string $context, array $options) : string{
		Render::push("<style>");
		$options["in_css"] = true;
		CssParser::render(Render::getUrl($context, $options), $options);
		return "</style>";
	}
}