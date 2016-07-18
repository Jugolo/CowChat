<?php

namespace inc\tempelate\driver;

use inc\driver\dir\DriverDir;
use inc\error\HeigLevelError;
use inc\interfaces\tempelate\TempelateInterface;

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
		foreach(new DriverDir("tempelate") as $driver){
			if($driver->isFile()){
				$this->drivers[$driver->getItemName()] = [
						"name"   => "inc\\driver\\tempelate\\" . $driver->getItemName()."\\TempelateDriver",
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