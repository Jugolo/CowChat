<?php
namespace inc\driver\file;

use inc\interfaces\driver_file\DriverFileItem;

class FileDriver implements DriverFileItem{
	private $path;
	
	public function __construct(string $path){
		$this->path = $path;
	}
	
	public function isFile() : bool{
		return true;
	}
	
	public function getItemName() : string{
		return pathinfo($this->path)["filename"];
	}
}