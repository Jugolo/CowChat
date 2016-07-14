<?php
namespace inc\message;

use inc\messageparser\MessageParser;
use inc\channel\ChannelData;
use inc\user\UserData;
use inc\system\System;
use inc\database\Database;

class Message{
	static function send(MessageParser $parser, $message){
		if($parser != null && $parser->hasPrefix()){
			$message = $parser->prefix() . "!" . $message;
		}
		
		echo $message . "\r\n";
	}
	static function send_privmsg(UserData $user, ChannelData $channel, $message){
		if(!$channel->isMember(User::current()) || !$channel->isMember($user)){
			send(null, "ERROR: Unknown channel");
			return;
		}
		Database::insert("message", [
				'uid' => User::current()->id(),
				'cid' => $channel->id(),
				'message' => $message,
				'isPriv' => 'Y',
				'privTo' => $user->id()
		]);
	}
	static function send_user(UserData $user, $msg){
		Database::insert("user_msg", [
				'uid' => $user->id(),
				'message' => $msg
		]);
	}
	static function send_channel(ChannelData $channel, UserData $user, $message){
		if(!$channel->isMember($user)){
			if($user == User::current()){
				send(null, "ERROR: notMember");
			}
			return false;
		}
		Database::insert("message", [
				'uid' => $user->id(),
				'cid' => $channel->id(),
				'message' => $message
		]);
		
		if(System::is_cli()){
			// wee got all members in channel
			foreach($channel->getMembers() as $member){
				$mask = mask($message);
				socket_write($member->getUser()->websocket(), $mask, strlen($mask));
			}
		}
		return true;
	}
}