<?php
namespace inc\tempelate\database;

use inc\error\HeigLevelError;
use inc\setting\Setting;

class TempelateDatabase{
	private $variabels = [];
	
	public function put(string $name, $value){
		$this->variabels[$name] = $value;
	}
	
	public function controled_array_get($array, $key){
		$array = $this->controled_get($array);
		if(!is_array($array)){
			throw new HeigLevelError("Coult not convert ".gettype($array)." to array");
		}
		
		if(!array_key_exists($key, $array)){
			throw new HeigLevelError("Unknown key to array");
		}
		
		return $array[$key];
	}
	
	public function controled_get(string $name){
		if(strpos($name, "SETTING_") === 0){
			$name = strtolower(substr($name, 8));
			if(!Setting::exists($name)){
				throw new HeigLevelError("Unknown setting value", $name);
			}
			
			return Setting::get($name);
		}
		if(!$this->exists($name)){
			throw new HeigLevelError("Unknown variabel", $name);
		}
		
		return $this->variabels[$name];
	}
	
	public function controled_callable($name){
		if($name == "exists"){
			return [$this, "exists"];
		}elseif($name == "language"){
			return ["inc\\language\\Language", "get_sprintf"];
		}
		$var = $this->controled_get($name);
		if(!function_exists($name) && !is_callable($name)){
			throw new HeigLevelError("Unknown function", $name);
		}
		return $var;
	}
	
	public function controled_object($name){
		$var = $this->controled_get($name);
		if(!is_object($var)){
			throw new HeigLevelError("Unknown object", $name);
		}
		
		return $var;
	}
	
	public function exists(string $name){
		return array_key_exists($name, $this->variabels);
	}
	
	public function show_items(){
		echo "<pre>";
		print_r($this->variabels);
		exit("</pre>");
	}
}