<?php
namespace inc\driver\tempelate\tempelate_image;

use inc\interfaces\tempelate\TempelateInterface;
use inc\tempelate\render\Render;
use inc\file\Files;
use inc\error\HeigLevelError;
use inc\image\Image;

class TempelateDriver implements TempelateInterface{
	public function allow_php_tag() : bool{
		return false;
	}
	
	public function render(string $context, array $options) : string{
		if(!Files::exists($context)){
			throw new HeigLevelError("Unknown image", $context);
		}
		
		return Image::base64_encode($context);
	}
}