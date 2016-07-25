<?php

namespace inc\javascript\start;

use inc\file\Files;
use inc\error\LowLevelError;
use inc\error\HeigLevelError;

class JavascriptCompressor{
	private static $buffer = "";
	private static $files = [];
	public static function append(string $file): bool{
		if(in_array($file, self::$files)){
			// pass the file
			return false;
		}else{
			// append file to buffer
			self::$files[] = $file;
			self::render($file);
			return true;
		}
	}
	public static function getContext(){
		return self::$buffer;
	}
	private static function render(string $file){
		if(!Files::exists($file)){
			throw new LowLevelError("Could not finde the javascript file", $file);
		}
		
		$context = Files::context($file);
		if(strpos($context, 'include("') !== false){
			while(($pos = strpos($context, 'include("')) !== false){
				//put in buffer to include(" is there
				self::$buffer .= substr($context, 0, $pos);
				$context = substr($context, $pos+9);
				if(($pos = strpos($context, '");')) !== false){
					$dir = substr($context, 0, $pos);
					self::append($dir);
					$context = substr($context, $pos+3);
				}else{
					throw new HeigLevelError("Missing \"); in include(\"url\");");
				}
			}
			
			self::$buffer .= $context;
		}else{
			self::$buffer .= $context;
		}
	}
}