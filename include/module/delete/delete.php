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
		default:
			Defender::updateCount(-0.002);
			error($message, "Unknown delete aguments");
			return;
		break;
	}
}