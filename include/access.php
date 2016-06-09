<?php
function allowChangeTitle($channelname, $user = null){
	if($user == null){
		$user = User::current();
	}
	
	if(($channel = Channel::get($channelname)) == null){
		// no
		return false;
	}
	
	if(!$channel->isMember($user)){
		return false;
	}
	
	if($channel->getMember($user)->group()->allowChangeTitle()){
		return true;
	}
	
	return false;
}
function allowIgnoreFlood($channelname, $user = null){
	if($user == null){
		$user = User::current();
	}
	
	if(($channel = Channel::get($channelname)) == null){
		// no
		return false;
	}
	
	if(!$channel->isMember($user)){
		return false;
	}
	
	if($channel->getMember($user)->group()->allowIgnoreFlood()){
		return true;
	}
	
	return false;
}
function allowKick($channelName, $user = null){
	if($user == null){
		$user = User::current();
	}
	
	if(($channel = Channel::get($channelName)) == null){
		// no
		return false;
	}
	
	if(!$channel->isMember($user)){
		return false;
	}
	
	if($channel->getMember($user)->group()->allowKick()){
		return true;
	}
	
	return false;
}