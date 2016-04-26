<?php
function join_command(MessageParser $message){
	if(controleChannelName($message->message())){
		if(Channel::join($message->message(), User::current(), $message)){
			title($message, $message->message(), Channel::get($message->message())->title());
		}
	}else{
        Defender::updateCount(-0.12);
		error($message, "Name on channel failed to valieate it");
	}
}
