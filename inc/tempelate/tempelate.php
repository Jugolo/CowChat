<?php
namespace inc\tempelate\tempelate;

use inc\file\Dirs;
use inc\file\Files;
use inc\error\HeigLevelError;
use inc\tempelate\render\Render;
use inc\tempelate\show\Show;
use inc\tempelate\database\TempelateDatabase;
use inc\temp\Temp;

class Tempelate{
	private $options = [];
	private $db;
	
	public function __construct(array $option){
		$this->options = array_merge([
				"no_prefix" => false,
				"cache"     => false,
				"in_js"     => false,
				"in_css"    => false,
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
		if(!$this->options["cache"]){
			$this->show($file);
		}else{
			$files = $this->get_cache_name($file);
			if(!Temp::exists($files[0], "tempelate") || !Temp::exists($files[1], "tempelate")){
				$this->show($file);
			}else{
				//wee get the time cache was last change or created
				if($this->isCacheFreach(Temp::changeTime($files[1], "tempelate"), json_decode(Temp::get($files[0], "tempelate"), true))){
					new Show(Temp::get($files[1], "tempelate"), $this->db);
				}else{
					$this->show($file);
				}
			}
		}
	}
	
	private function isCacheFreach(int $time, array $list){
		foreach($list as $file){
			if($time < filemtime($file)){
				return false;
			}
		}
		
		return true;
	}
	
	private function show(string $file){
		Render::reseat();
		$cache = $this->get_cache_name($file);
		$file = $this->get_file_name($file);
		if(!Files::exists($file)){
			throw new HeigLevelError("Unknown file: ", $file);
		}
		$context = Render::render($file, $this->options);
		if($this->options["cache"]){
			//build information file
			Temp::create($cache[0], json_encode(Render::getFilesList()) ,"tempelate");
			Temp::create($cache[1], $context, "tempelate");
		}
		new Show($context, $this->db);
		Render::reseat();
	}
	
	private function get_cache_name(string $file) : array{
		$dir = "";
		if(array_key_exists("dir", $this->options) && Dirs::isDir($this->options["dir"])){
			$dir = $this->options["dir"];
		}
		
		return [
				$dir.$file.".info",
				$dir.$file.".soruce"
		];
	}
	
	private function get_file_name(string $file) : string{
		$dir = "";
		if(array_key_exists("dir", $this->options) && Dirs::isDir($this->options["dir"])){
			$dir = $this->options["dir"];
		}
		
		return $dir.$file.($this->options["no_prefix"] ? "" : ".tempalte");
	}
}