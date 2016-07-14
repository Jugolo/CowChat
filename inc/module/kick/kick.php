<?php
use inc\messageparser\MessageParser;
use inc\user\User;
function kick_command(MessageParser $parser){
	// control my membership of the channel
	if($parser->channel()->isMember(User::current())){
		// okay let try to find out if the user is allow to kick user
		if(allowKick($parser->channelName())){
			// controle if the user is exists
			if(($u = User::get($parser->message())) != null){
				// controle if the user kick her self
				if($u->id() != User::current()->id()){
					// controle if the user is member of the channel
					if($parser->channel()->isMember($u)){
						$parser->channel()->send("KICK " . $parser->channelName() . ": " . $u->nick());
						send_user($u, "KICK " . $parser->channelName() . ": " . $u->nick());
						$parser->channel()->leave($u, false);
					}else{
						error($parser, "The user is not member of the channel");
					}
				}else{
					error($parser, "You can`t kick you self");
				}
			}else{
				error($parser, "Unknown user to kick");
			}
		}else{
			error($parser, "You are not allow to kick user in the channel");
		}
	}else{
		error($parser, "You are not member of the channel");
	}
}