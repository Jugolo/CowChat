<?php
namespace inc\user\stack;

use inc\database\Database;
use inc\error\HeigLevelError;
use inc\user\data\UserData;

class UserStack{
	private $pointer = 0;
	private $stack   = [];
	
	public function push(string $username){
		$data = $this->getData($username);
		$this->stack[] = new UserData($username, $data["id"], $data["data"]);
	}
	
	public function getCount() : int{
		return count($this->stack);
	}
	
	public function current() : UserData{
		$this->controle();
		return $this->stack[$this->pointer];
	}
	
	private function getData(string $username) : array{
		$query = Database::getInstance()->query("SELECT d.*, l.id AS uid  FROM ".table("user_data")." AS d
				LEFT JOIN ".table("user_login")." AS l ON d.uid=l.id
				WHERE l.username=".Database::getInstance()->clean($username));
		$row = $query->fetch();
		if($row == null){
			throw new HeigLevelError("Unknown user", $nick);
		}
		$id = $row["uid"];
		unset($row["uid"]);
		return ["id" => $id, "data" => $row];
	}
	
	private function controle(){
		if($this->getCount()-1 < $this->pointer){
			throw new HeigLevelError("Could not find the user", "Pointer: ".$this->pointer);
		}
	}
}