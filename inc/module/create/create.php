<?php
use inc\messageparser\MessageParser;
use inc\access\Access;
use inc\defender\Defender;
use inc\database\Database;

function create_command(MessageParser $message){
	$command = explode(" ", $message->message());
	if(count($command) == 0){
		error($message, "/create command has not enaoug data");
		return;
	}
	
	switch($command[0]){
		case "UserGroup":
			if(!Access::allowCreateUserGroup()){
				// tell the defender to change count for the user
				Defender::updateCount(-0.5);
				error($message, "Access denaid");
				return;
			}
			if(count($command) <= 1){
				Defender::updateCount(-0.001); // soft error no need to take count to fast for this
				error($message, "Missing agument for /create UserGroup");
				return;
			}
			//find out if a group has the name
			$query = Database::query("SELECT COUNT(`id`) AS id FROM ".table("user_group")." WHERE `name`=".Database::qlean($command[1]));
			$row = $query->fetch();
			if($row["id"] != 0){
				Defender::updateCount(-0.001);
				error($message, "The group exists");
				return;
			}
			Database::insert("user_group", [
					"name" => $command[1]
			]);
			send($message, "CREATE: UserGroup ".$command[1]);
		break;
		default:
			Defender::updateCount(-0.002);
			error($message, "Unknown create aguments");
			return;
		break;
	}
}