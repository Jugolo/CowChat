<?php
function join_command(MessageParser $message){
	if(strpos($message->message(), "#") === 0){
		if(Channel::join($message->message(), User::current(), $message)){
			title($message, $message->message(), Channel::get($message->message())->title());
		}
	}else{
                Defender::updateCount(-0.12);
		error($message, "A channel need to start width #");
	}
}
