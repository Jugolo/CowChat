<?php
namespace inc\tempelate\database;

class TempelateDatabase{
	private $variabels = [];
	
	public function put(string $name, $value){
		$this->variabels[$name] = $value;
	}
}