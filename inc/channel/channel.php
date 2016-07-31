<?php
namespace inc\channel\channel;

use inc\database\Database;
use inc\error\HeigLevelError;
use inc\system\System;
use inc\user\User;
use inc\user\data\UserData;

class Channel{
	private $data;
	public function __construct(int $id){
		$query = Database::getInstance()->query("SELECT * FROM ".table("channel")." WHERE `id`='".$id."'");
		$row = $query->fetch();
		$query->free();
		if($row === null){
			throw new HeigLevelError("Unknown channel", "id: ".$id);
		}
		$this->data = $row;
	}
	
	public function getId() : int{
		return $this->data["id"];
	}
	
	public function getName() : string{
		return $this->data["name"];
	}
	
	public function getStandartGroupId() : int{
		return $this->data["start_group"];
	}
	
	public function setStandartGroupId(int $id){
		$this->update("start_group", $id);
	}
	
	public function send(UserData $user, string $message){
		if(System::is_cli()){
			
		}
		Database::insert("message", [
				"cid"     => $this->getId(),
				"uid"     => $user->getUserId(),
				"message" => $message,
				"time"    => ["NOW()"]
		]);
	}
	
	private function update(string $key, $value){
		$database = Database::getInstance();
		$database->query("UPDATE ".table("channel")." SET `".$key."`=".$database->clean($value)." WHERE `id`='".$this->getId()."'");
	}
}