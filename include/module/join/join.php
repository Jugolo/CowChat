<?php
function join_command(MessageParser $message){
	if(controleChannelName($message->message())){
		Channel::join($message->message(), User::current(), $message);
	}else{
		Defender::updateCount(-0.12);
		error($message, "Name on channel failed to valieate it");
	}
}
