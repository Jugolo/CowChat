<?php

namespace inc\tempelate\driver;

use inc\error\HeigLevelError;
use inc\interfaces\tempelate\TempelateInterface;
use inc\file\Dirs;

class TempelateDriver{
	public static function getInstance(){
		static $self = null;
		if($self === null){
			$self = new TempelateDriver();
		}
		
		return $self;
	}
	
	private $drivers;
	
	public function __construct(){
		$this->drivers = [];
		$dir = Dirs::openDir("inc/driver/tempelate");
		while($name = readdir($dir)){
			$item = "inc/driver/tempelate/".$name;
			if(is_file($item)){
				$clean = pathinfo($item, PATHINFO_FILENAME);
				$this->drivers[$clean] = [
						"name"   => str_replace("/", "\\", "inc/driver/tempelate/".$clean)."\\TempelateDriver",
						"object" => null
				];
			}
		}
	}
	
	public function getDriver(string $name) : TempelateInterface{
		if(!$this->exists($name))
			throw new HeigLevelError("Unknown driver", $name);
		$name = "tempelate_".$name;
		if($this->drivers[$name]["object"] === null){
			$obj_name = $this->drivers[$name]["name"];
			$this->drivers[$name]["object"] = new $obj_name();
		}
		
		return $this->drivers[$name]["object"];
	}
	
	public function exists(string $name) : bool{
		return array_key_exists("tempelate_".$name, $this->drivers);
	}
}