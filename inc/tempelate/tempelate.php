<?php
namespace inc\tempelate\tempelate;

use inc\file\Dirs;
use inc\file\Files;
use inc\error\HeigLevelError;
use inc\tempelate\render\Render;
use inc\tempelate\show\Show;
use inc\tempelate\database\TempelateDatabase;

class Tempelate{
	private $options = [];
	private $db;
	
	public function __construct(array $option){
		$this->options = array_merge([
				"no_prefix" => false
		], $option);
		
		if(array_key_exists("dir", $this->options)){
			if(strrpos($this->options["dir"], "/") !== strlen($this->options["dir"])-1){
				$this->options["dir"] .= "/";
			}
		}
		
		$this->db = new TempelateDatabase();
	}
	
	public function add_var_array(array $data){
		foreach($data as $key => $value){
			$this->add_var($key, $value);
		}
	}
	
	public function getOptionArray() : array{
		return $this->options;
	}
	
	public function add_var(string $name, $context){
		$this->db->put($name, $context);
	}
	
	public function exec(string $file){
		$file = $this->get_file_name($file);
		if(!Files::exists($file)){
			throw new HeigLevelError("Unknown file: ", $file);
		}
		
		$render = new Render(Files::context($file), $this);
		$render->render();
		//exit($render->getContext());
		new Show($render->getContext(), $this->db);
	}
	
	public function getCompiledSource(string $file) : string{
		$file = $this->get_file_name($file);
		if(!Files::exists($file)){
			throw new HeigLevelError("Unknown file: ", $file);
		}
		
		$render = new Render(Files::context($file), $this);
		$render->render();
		return $render->getContext();
	}
	
	private function get_file_name(string $file) : string{
		$dir = "";
		if(array_key_exists("dir", $this->options) && Dirs::isDir($this->options["dir"])){
			$dir = $this->options["dir"];
		}
		
		return $dir.$file.($this->options["no_prefix"] ? "" : ".tempalte");
	}
}