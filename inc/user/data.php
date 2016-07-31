<?php
namespace inc\user\data;

class UserData{
	private $buffer;
	
	public function __construct(array $data){
		$this->buffer = $data;
	}
	
	public function getUserId(){
		return $this->buffer["id"];
	}
	
	public function getUsername(){
		return $this->buffer["username"];
	}
	
	public function getEmail(){
		return $this->buffer["email"];
	}
	
	public function getNick(){
		return $this->buffer["nick"];
	}
	
	public function getLastId(){
		return $this->buffer["last_id"];
	}
}