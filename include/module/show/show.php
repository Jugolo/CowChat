<?php
function show_command(MessageParser $message){
	$command = explode(" ", $message->message());
	if(count($command) == 0){
		error($message, "/show command has not enaoug data");
		return;
	}
	
	if($command[0] == "ip"){
		if(!allowShowIp()){
			// tell the defender to change count for the user
			Defender::updateCount(-0.5);
			error($message, "Access denaid");
			return;
		}
		
		if(count($command) <= 1){
			Defender::updateCount(-0.001); // soft error no need to take count to fast for this
			error($message, "Missing agument for /show ip");
			return;
		}
		
		switch($command[1]){
			case "blacklisted":
				send($message, "SHOW: blacklisted " . implode(",", FireWall::getBlacklist()));
			break;
			case "whitelisted":
				send($message, "SHOW: whitelisted " . implode(",", FireWall::getWhiteList()));
			break;
			case "ban":
				send($message, "SHOW: ban " . implode(",", FireWall::getBans()));
			break;
			case "info":
				if(count($command) <= 2){
					Defender::updateCount(-0.001);
					error($message, "Missing info for /show info [ip]");
					return;
				}
				send($message, "SHOW: info " . showIpInfo(FireWall::getInfoBan($command[2])));
			break;
			default:
				Defender::updateCount(-0.002);
				error($message, "Unknown show aguments");
				return;
			break;
		}
	}elseif($command[0] == "defender"){
		if(!allowShowDefender()){
			// tell the defender to change count for the user
			Defender::updateCount(-0.5);
			error($message, "Access denaid");
			return;
		}
		
		if(count($command) <= 1){
			Defender::updateCount(-0.001); // soft error no need to take count to fast for this
			error($message, "Missing agument for /show defender");
			return;
		}
		
		switch($command[1]){
			case "count":
				if(count($command) <= 2){
					Defender::updateCount(-0.001);
					error($message, "Missing info for /show defender count [nick]");
					return;
				}
				
				// try to find the user
				if(($user = User::get($command[2])) !== null){
					send($message, "SHOW: defender " . $command[2] . "," . $user->defenderCount());
				}else{
					error($message, "Unknown user");
				}
			break;
			default:
				Defender::updateCount(-0.002);
				error($message, "Unknown show aguments");
				return;
			break;
		}
	}else if($command[0] == "UserGroup"){
		if(!allowShowUserGroup()){
			// tell the defender to change count for the user
			Defender::updateCount(-0.5);
			error($message, "Access denaid");
			return;
		}
		
		if(count($command) <= 1){
			Defender::updateCount(-0.001); // soft error no need to take count to fast for this
			error($message, "Missing agument for /show UserGroup");
			return;
		}
		
		switch($command[1]){
			case "access":
				if(count($command) <= 2){
					Defender::updateCount(-0.001);
					error($message, "Missing info for /show UserGroup access [name]");
					return;
				}
				
				if(($group = get_user_group($command[2]))){
					$parts = [];
					foreach($group->getAccessList() as $key => $value)
						$parts[] = $key . "=" . $value;
					send($message, "SHOW: access " . implode(",", $parts));
				}else{
					error($message, "Unknown group");
				}
			break;
			case "members":
				if(count($command) <= 2){
					Defender::updateCount(-0.001);
					error($message, "Missing info for /show UserGroup members [name]");
					return;
				}
				
				if(($group = get_user_group($command[2])) !== null){
					$query = Database::query("SELECT `nick` FROM ".table("user")." WHERE `groupId`='".$group->id()."'");
					$buffer = [];
					while($row = $query->fetch()){
						$buffer[] = $row['nick'];
					}
					send($message, "SHOW: members ".implode(",", $buffer));
				}else{
					error($message, "Unknown group");
				}
			break;
			default:
				Defender::updateCount(-0.002);
				error($message, "Unknown show aguments");
				return;
			break;
		}
	}elseif($command[0] == "user"){
		if(!allowShowUser()){
			// tell the defender to change count for the user
			Defender::updateCount(-0.5);
			error($message, "Access denaid");
			return;
		}
		
		if(count($command) <= 1){
			Defender::updateCount(-0.001); // soft error no need to take count to fast for this
			error($message, "Missing agument for /show user");
			return;
		}
		switch($command[1]){
			case "type":
				if(count($command) <= 2){
					Defender::updateCount(-0.001);
					error($message, "Missing info for /show user type [nick]");
					return;
				}
				
				if(($user = User::get($command[2])) !== null){
					send($message, "SHOW: type ".$user->nick().",".$user->type());
				}else{
					error($message, "Unknown user");
				}
			break;
			case "channels":
				if(count($command) <= 2){
					Defender::updateCount(-0.001);
					error($message, "Missing info for /show user channels [nick]");
					return;
				}
				
				if(($user = User::get($command[2])) !== null){
					$channels = [];
					$user->renderChannels(function(ChannelData $channel) use(&$channels){
						$channels[] = $channel->name();
						return false;
					});
					send($message, "SHOW: channels ".$user->nick().",".implode(",", $channels));
				}else{
					error($message, "Unknown user");
				}
			break;
			case "onlinestatus":
				if(count($command) <= 2){
					Defender::updateCount(-0.001);
					error($message, "Missing info for /show user onlinestatus [nick]");
					return;
				}
				
				if(($user = User::get($command[2])) !== null){
					$isOnline = false;
					if(Server::is_cli()){
						$isOnline = $user->websocket() != null;
					}else{
						$isOnline = $user->countUpdatet() > time()-300;
					}
					send($message, "SHOW: onlinestatus ".$user->nick().",".($isOnline ? "Online" : "Offline"));
				}else{
					error($message, "Unknown user");
				}
			break;
			default:
				Defender::updateCount(-0.002);
				error($message, "Unknown show aguments");
				return;
				break;
		}
	}else{
		error($message, "Unknown show agument: " . $command[0]);
	}
}
function showIpInfo(array $info){
	return $info["ip"] . "," . date("d-m-Y H:i:s", $info['expired']);
}
