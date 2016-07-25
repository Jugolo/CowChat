<?php
use inc\messageparser\MessageParser;
use inc\access\Access;
use inc\defender\Defender;
use inc\user\User;
function unset_command(MessageParser $message){
	$command = explode(" ", $message->message());
	
	switch($command[0]){
		case "defender":
			if(!allowUnsetDefender()){
				// tell the defender to change count for the user
				Defender::updateCount(-0.5);
				error($message, "Access denaid");
				return;
			}
			
			if(count($command) <= 1){
				Defender::updateCount(-0.001); // soft error no need to take count to fast for this
				error($message, "Missing agument for /unset defender");
				return;
			}
			
			if(($user = User::get($command[1])) != null){
				$user->defenderCount(0.5);
				send($message, "UNSET: defender success");
			}else{
				error($message, "Unknown user");
			}
		break;
		default:
			error($message, "Unknown /unset command");
		break;
	}
}