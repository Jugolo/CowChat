<?php
function join_command(MessageParser $message){
	if(controleChannelName($message->message())){
		if(!Channel::join($message->message(), User::current(), $message)){
			error($message, "Somthink width the join failed");
		}
	}else{
        Defender::updateCount(-0.12);
		error($message, "Name on channel failed to valieate it");
	}
}
