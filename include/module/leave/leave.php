<?php
function leave_command(MessageParser $parser){
	if(($channel = Channel::get($parser->message())) != null){
		$user = User::current();
		if($user->isMember($channel->name())){
			$user->leave($channel, "Leave the channel");
			send($parser, "LEAVE: " . $parser->message());
		}else{
			Defender::updateCount(-0.1);
			error($parser, "You are not member of the channel");
		}
	}else{
		Defender::updateCount(-0.1);
		error($parser, "Unknown channel");
	}
}
