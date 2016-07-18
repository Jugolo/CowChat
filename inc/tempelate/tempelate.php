<?php
namespace inc\tempelate\tempelate;

use inc\file\Dirs;
use inc\file\Files;
use inc\error\HeigLevelError;
use inc\tempelate\render\Render;

class Tempelate{
	private $options = [];
	
	public function __construct(array $option){
		$this->options = array_merge([
				"no_prefix" => false
		], $option);
		
		if(array_key_exists("dir", $this->options)){
			if(strrpos($this->options["dir"], "/") !== strlen($this->options["dir"])-1){
				$this->options["dir"] .= "/";
			}
		}
	}
	
	public function exec(string $file){
		$file = $this->get_file_name($file);
		if(!Files::exists($file)){
			throw new HeigLevelError("Unknown file: ", $file);
		}
		
		$render = new Render(Files::context($file));
		$render->render();
		exit($render->getContext());
		eval(' ?>'.$render->getContext().'<?php ');
	}
	
	private function get_file_name(string $file) : string{
		$dir = "";
		if(array_key_exists("dir", $this->options) && Dirs::isDir($this->options["dir"])){
			$dir = $this->options["dir"];
		}
		
		return $dir.$file.($this->options["no_prefix"] ? "" : ".tempalte");
	}
}