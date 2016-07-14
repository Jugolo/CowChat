<?php
namespace inc\user\data;

class UserData{
	private $data = [];
	
	public function __construct(array $data){
		$this->data = $data;
	}
}