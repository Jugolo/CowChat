<?php
use inc\messageparser\MessageParser;
use inc\user\User;
function nick_command(MessageParser $msg){
	if(!User::current()->nick($msg->message())){
		error($msg, "Nick is taken");
	}
}
