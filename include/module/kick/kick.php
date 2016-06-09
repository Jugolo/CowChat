<?php
function kick_command(MessageParser $parser){
	//control my membership of the channel 
	if($parser->channel()->isMember(User::current())){
		//okay let try to find out if the user is allow to kick user
		if(allowKick($parser->channelName())){
			//controle if the user is exists
			if(($u = User::get($parser->message())) != null){
				//controle if the user is member of the channel
				if($parser->channel()->isMember($u)){
					$parser->channel()->send("KICK ".$parser->channelName().": ".$u->nick());
					$parser->channel()->leave($u, false);
				}else{
					error($parser, "The user is not member of the channel");
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