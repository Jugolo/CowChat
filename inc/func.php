<?php

use inc\database\Database;
use inc\system\System;


function ip($ws = null){
	if(!defined("IN_SETUP") && System::is_cli()){
		if(socket_getpeername(($ws !== null ? $ws : User::current()->websocket()), $ip)){
			if($ip == "::1")
				$ip = "127.0.0.1";
				return $ip;
		}
		return null;
	}

	return $_SERVER['REMOTE_ADDR'] == "::1" ? "127.0.0.1" : $_SERVER["REMOTE_ADDR"];
}

function table(string $name) : string{
	return "`" . Database::$prefix . "_" . $name . "`";
}
function nick_taken($nick, UserData $user = null){
	$database = Database::getInstance();
	$nick = $database->clean($nick);
	$sql = "SELECT COUNT(`id`) AS id FROM " . table("user") . " WHERE (`nick`=" . $nick . " OR `username`=" . $nick . ")";
	
	if($user != null){
		$sql .= " AND `id`<>'" . $user->id() . "'";
	}
	
	return $database->query($sql)->fetch()["id"] != 0;
}