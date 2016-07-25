<?php
function get_command(MessageParser $message){
	$command = explode(" ", $message->message());
	if(count($command) == 0){
		error($message, "/show command has not enaoug data");
		return;
	}
	
	switch($command[0]){
		case "id":
			if(count($command) <= 2){
				Defender::updateCount(-0.001); // soft error no need to take count to fast for this
				error($message, "Missing agument for /get id");
				return;
			}
			switch($command[1]){
				case "UserGroup":
					if(!allowGetUserGroupId()){
						Defender::updateCount(0.15);
						error($message, "Access denaid");
						return;
					}
					//find out if the usergroup exists
					$query = Database::query("SELECT `id`, `name` FROM ".table("user_group")." WHERE `name`=".Database::qlean($command[2]));
					if($query->rows() != 1){
						Defender::updateCount(-0.001);
						error($message, "Unknown group");
						return;
					}
					$row = $query->fetch();
					send($message, "GET: id UserGroup ".$row["id"]." ".$row["name"]);
				break;
				default:
					Defender::updateCount(-0.002);
					error($message, "Unknown delete aguments");
					return;
				break;
			}
		break;
		default:
			Defender::updateCount(-0.002);
			error($message, "Unknown delete aguments");
			return;
		break;
	}
}