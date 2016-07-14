<?php
namespace inc\user\helper;

use inc\user\data\UserData;
use inc\database\Database;
use inc\setting\Setting;
use inc\system\System;

class UserHelper{
	public function nick_taken($nick, UserData $data = null) : bool{
		$database = Database::getInstance();
		$sql = "SELECT COUNT(u.id) AS id FROM ".table("user_login")." AS u
				LEFT JOIN ".table("user_data")." AS d ON d.uid=u.id
				WHERE (u.username=".($nick = $database->clean($nick))." OR d.nick=".$nick.")";
		
		if($data != null){
			$sql .= " AND `id`<>'".$data->id()."'";
		}
		return $database->query($sql)->fetch()["id"] != 0;
	}
	
	public function email_taken(string $email) : bool{
		return Database::getInstance()->query("SELECT `email` FROM ".table("user_login")." WHERE `email`=".Database::getInstance()->clean($email))->fetch() !== null;
	}
	
	public function apppend_user(int $id, string $nick, string $type) : UserData{
		//defender insert
		Database::insert("user_defender", [
				"uid"     => $id,
				"count"   => 0.15,
				"updatet" => time(),
		]);
		
		Database::insert("user_data", ($data = [
				"uid"        => $id,
				"groupId"    => Setting::get("startGroup"),
				"message_id" => System::getLastId(),
				"type"       => $type,
				"nick"       => $nick
		]));
		
		return $data;
	}
}