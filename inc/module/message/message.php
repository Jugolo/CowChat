<?php
use inc\messageparser\MessageParser;
use inc\database\Database;
use inc\user\User;

function message_command(MessageParser $message){
	$message->encode();
	if($message->channel() != null){
		if($message->channel()->isMember(User::current())){
			if(!empty(($msg = $message->message())) && trim($msg)){
				$message->channel()->send("MESSAGE " . $message->channelName() . ": " . $message->message());
				$message->channel()->updateActive(User::current());
			}else{
				error($message, "You can not send empty message");
			}
		}else{
			error($message, "You are not member of the channel");
		}
	}else{
		if(substr($channel->channelName(), 0, 1) == "#"){
			// controle if wee got a user width that name
			if(($user = User::get($channel->channelName())) != null){
				// insert into pm table so the user can se it
				Database::insert("pm", [
						"from" => User::current()->id(),
						"to" => $user->id(),
						"msg" => $message->message()
				]);
				send($message, "MESSAGE " . $user->nick() . ": " . $message);
			}else{
				error($message, "Unknown user");
			}
		}else{
			error($message, "Unknown channel");
		}
	}
}
