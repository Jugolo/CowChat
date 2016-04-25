<?php 
function leave(MessageParser $parser){
	if(($channel = Channel::get($parser->message())) != null){
		$user = User::current();
		if($user->isMember($channel)){
			$user->leave($channel, "Leave the channel");
			send($parser, "LEAVE: ".$parser->message());
		}else{
			error($parser, "You are not member of the channel");
		}
	}else{
		error($parser, "Unknown channel");
	}
}