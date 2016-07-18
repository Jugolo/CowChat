<?php
use inc\messageparser\MessageParser;
use inc\user\User;
function online_command(MessageParser $msg){
	if(User::current()->isMember($msg->message())){
		$members = [];
		foreach(Channel::get($msg->message())->getMembers() as $member){
			$members[] = $member->getUser()->nick();
		}
		send($msg, "ONLINE " . Channel::get($msg->message())->name() . ": " . implode(",", $members));
	}else{
		error($msg, "You are not member of the channel");
	}
}
