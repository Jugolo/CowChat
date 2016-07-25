<?php
namespace inc\driver\tempelate\tempelate_javascript;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;

class TempelateDriver implements TempelateInterface{
	public function allow_php_tag() : bool{
		return true;
	}
	
	public function render(string $context, Render $render) : string{
		return "inc\\javascript\\start\\JavascriptCompressor::append('".$context."');\r\necho '<script>'.inc\\javascript\\start\\JavascriptCompressor::getContext().'</script>';";
	}
}