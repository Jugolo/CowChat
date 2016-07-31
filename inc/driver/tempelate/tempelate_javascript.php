<?php
namespace inc\driver\tempelate\tempelate_javascript;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;

class TempelateDriver implements TempelateInterface{
	public function allow_php_tag() : bool{
		return false;
	}
	
	public function render(string $context, array $options) : string{
		$options["in_js"] = true;
		Render::push("<script>");
		Render::render($context, $options);
		return "</script>";
	}
}