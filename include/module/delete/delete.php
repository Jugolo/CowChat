<?php
function delete_command(MessageParser $message){
	$command = explode(" ", $message->message());
	if(count($command) == 0){
		error($message, "/show command has not enaoug data");
		return;
	}
	
	switch($command[0]){
		case "ip":
			if(!allowDeleteIp()){
				// tell the defender to change count for the user
				Defender::updateCount(-0.5);
				error($message, "Access denaid");
				return;
			}
			if(count($command) <= 1){
				Defender::updateCount(-0.001); // soft error no need to take count to fast for this
				error($message, "Missing agument for /delete ip");
				return;
			}
			
			if(FireWall::isBlacklist($command[1])){
				error($message, "The ip is blacklisted");
				return;
			}
			
			$query = Database::query("DELETE FROM ".table("ip_ban")." WHERE `ip`=".Database::qlean($command[1]));
			if($query->rows() == 0){
				error($message, "Unknown ip");
				return;
			}
			send($message, "DELETE: ip ".$command[1]);
		break;
		case "user":
			if(!allowDeleteUser()){
				// tell the defender to change count for the user
				Defender::updateCount(-0.5);
				error($message, "Access denaid");
				return;
			}
			
			if(count($command) <= 1){
				Defender::updateCount(-0.001); // soft error no need to take count to fast for this
				error($message, "Missing agument for /delete user");
				return;
			}
			
			//try to see if the user exists
			if(($user = User::get($command[1])) == null){
				Defender::updateCount(-0.001); // soft error no need to take count to fast for this
				error($message, "Unknown user");
				return;
			}
			
			//delete the user 
			$user->delete();
			send($message, "DELETE: user ".$user->nick());
		break;
		case "UserGroup":
			if(count($command) <= 2){
				Defender::updateCount(-0.001); // soft error no need to take count to fast for this
				error($message, "Missing agument for /delete UserGroup");
				return;
			}
			if($command[1] == "group"){
				if(!allowDeleteUserGroup()){
					// tell the defender to change count for the user
					Defender::updateCount(-0.5);
					error($message, "Access denaid");
					return;
				}
				//find out if wee got the group
				$query = Database::query("SELECT `id` FROM ".table("user_group")." WHERE `name`=".Database::qlean($command[2]));
				if($query->rows() != 1){
					Defender::updateCount(-0.001); // soft error no need to take count to fast for this
					error($message, "Unknown user group");
					return;
				}
				$row = $query->fetch();
				$group = new UserGroup($row["id"]);
				if($group->getMembersRow() !== 0){
					Defender::updateCount(-0.001); // soft error no need to take count to fast for this
					error($message, "Cant delete user group where there is member");
					return;
				}
				if($group->delete()){
					send($message, "DELETE: UserGroup group ".$group->name());
				}else{
					Defender::updateCount(-0.001); // soft error no need to take count to fast for this
					error($message, "delete user group failded");
				}
			}elseif($command[1] == "access"){
				
			}else{
				Defender::updateCount(-0.002);
				error($message, "Unknown delete aguments");
				return;
			}
		break;
		default:
			Defender::updateCount(-0.002);
			error($message, "Unknown delete aguments");
			return;
		break;
	}
}