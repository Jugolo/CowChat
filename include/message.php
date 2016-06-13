<?php
function send(MessageParser $parser, $message){
	if($parser->hasPrefix()){
		$message = $parser->prefix() . "!" . $message;
	}
	
	echo $message . "\r\n";
}
function send_privmsg(UserData $user, ChannelData $channel, $message){
	if(!$channel->isMember(User::current()) || !$channel->isMember($user)){
		send(new MessageParser("NO: COMMAND"), "ERROR: Unknown channel");
		return;
	}
	Database::insert("message", [
			'uid'     => User::current()->id(),
			'cid'     => $channel->id(),
			'message' => $message,
			'isPriv'  => 'Y',
			'privTo'  => $user->id()
	]);
}

function send_user(UserData $user, $msg){
	Database::insert("user_msg", [
			'uid'     => $user->id(),
			'message' => $msg,
	]);
}

function send_channel(ChannelData $channel, UserData $user, $message){
	if(!$channel->isMember($user)){
		if($user == User::current()){
			send(new MessageParser("UNKNOWN: no message"), "ERROR: notMember");
		}
		return false;
	}
	Database::insert("message", [
			'uid' => $user->id(),
			'cid' => $channel->id(),
			'message' => $message
	]);
	
	if(Server::is_cli()){
		WebSocket::send($channel, $message);
	}
	return true;
}
