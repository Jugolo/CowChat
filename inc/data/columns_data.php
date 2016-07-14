<?php
namespace inc\data\columns_data;

class ColumnsData{
	private $name;
	public function __construct(string $name){
		$this->name = $name;
	}
	
	public function getName() : string{
		return $this->name;
	}
}