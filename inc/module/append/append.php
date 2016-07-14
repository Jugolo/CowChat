<?php
use inc\messageparser\MessageParser;
use inc\access\Access;
use inc\defender\Defender;
use inc\database\Database;

function append_command(MessageParser $message){
	$command = explode(" ", $message->message());
	if(count($command) == 0){
		error($message, "/append command has not enaoug data");
		return;
	}
	
	switch($command[0]){
		case "UserGroup":
			if($command[1] == "access"){
				if(!Access::allowAppendUserGroupAccess()){
					Defender::updateCount(-0.001);
					error($message, "Access deniad");
					return;
				}
				
				if(count($command) <= 3){
					Defender::updateCount(-0.001); // soft error no need to take count to fast for this
					error($message, "Missing agument for /append UserGroup");
					return;
				}
				
				$query = Database::query("SELECT `id` FROM " . table("user_group") . " WHERE `name`=" . Database::qlean($command[2]));
				if($query->rows() != 1){
					Defender::updateCount(-0.001);
					error($message, "Unknown group");
					return;
				}
				
				$row = $query->fetch();
				$group = new UserGroup($row["id"]);
				if(!array_key_exists($command[3], $group->getAccessList())){
					Defender::updateCount(-0.001);
					error($message, "Unknown access name. Try /show UserGroup access [group]");
					return;
				}
				
				// wee try to find out if the group allready has access to the commando
				if($group->hasAccess($command[3])){
					Defender::updateCount(-0.001);
					error($message, "The group has allready access to the commando");
					return;
				}
				
				if($group->appendAccess($command[3])){
					send($message, "APPEND: UserGroup access " . $command[3] . " " . $group->name());
				}else{
					error($message, "Fail to append access to the group");
				}
			}elseif($command[1] == "user"){
				if(!Access::allowAppendUSerGroupUser()){
					Defender::updateCount(-0.001);
					error($message, "Access deniad");
					return;
				}
				
				if(count($command) <= 3){
					Defender::updateCount(-0.001); // soft error no need to take count to fast for this
					error($message, "Missing agument for /append UserGroup");
					return;
				}
				
				//try to find the user in the user :)
				if(($user = User::get($command[2])) === null){
					Defender::updateCount(-0.001);
					error($message, "Unknown user");
					return;
				}
				
				if($user->group()->name() == $command[3]){
					Defender::updateCount(-0.001);
					error($message, "The user is allready member of the group");
					return;
				}
				
				//wee try to find the group
				$query = Database::query("SELECT `id` FROM ".table("user_group")." WHERE `name`=".Database::qlean($command[3]));
				if($query->rows() != 1){
					Defender::updateCount(-0.001);
					error($message, "Unknown group");
					return;
				}
				$row = $query->fetch();
				if($user->groupId($row["id"]) == $row["id"]){
					send($message, "APPEND: UserGroup user " . $user->nick() . " " . $user->group()->name());
				}else{
					Defender::updateCount(-0.001);
					error($message, "Failed to append user in the group");
					return;
				}
			}else{
				Defender::updateCount(-0.002);
				error($message, "Unknown append aguments");
				return;
			}
		break;
		default:
			Defender::updateCount(-0.002);
			error($message, "Unknown append aguments");
			return;
		break;
	}
}