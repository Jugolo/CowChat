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
function allowShowIp(){
    return User::current()->group()->show_ip();
}
function allowShowDefender(){
	return User::current()->group()->show_defender();
}
function allowShowUserGroup(){
	return User::current()->group()->show_user_group();
}
function allowShowUser(){
	return User::current()->group()->show_user();
}
function allowUnsetDefender(){
	return User::current()->group()->unset_defender();
}
function allowDeleteIp(){
	return User::current()->group()->delete_ip();
}
function allowDeleteUser(){
	return User::current()->group()->delete_user();
}
function allowDeleteUserGroup(){
	return User::current()->group()->delete_user_group();
}
function allowDeleteUserGroupAccess(){
	return User::current()->group()->delete_user_group_access();
}
function allowCreateUserGroup(){
	return User::current()->group()->cretaeUserGroup();
}