<?php
namespace inc\user\data;

class UserData{
	private $data = [];
	private $id;
	private $username;
	
	public function __construct(string $username, int $id, array $data){
		$this->data = $data;
		$this->id   = $id;
		$this->username = $username;
	}
	
	public function getUsername() : string{
		return $this->username;
	}
	
	public function getNick() : string{
		return $this->data["nick"];
	}
	
	public function getId() : int{
		return $this->id;
	}
}