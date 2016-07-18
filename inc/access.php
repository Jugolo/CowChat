<?php
namespace inc\access;

use inc\user\User;
use inc\channel\Channel;

class Access{
	static function allowChangeTitle($channelname, $user = null){
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
	static function allowIgnoreFlood($channelname, $user = null){
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
	static function allowKick($channelName, $user = null){
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
	static function allowShowIp(){
		return User::current()->group()->show_ip();
	}
	static function allowShowDefender(){
		return User::current()->group()->show_defender();
	}
	static function allowShowUserGroup(){
		return User::current()->group()->show_user_group();
	}
	static function allowShowUser(){
		return User::current()->group()->show_user();
	}
	static function allowUnsetDefender(){
		return User::current()->group()->unset_defender();
	}
	static function allowDeleteIp(){
		return User::current()->group()->delete_ip();
	}
	static function allowDeleteUser(){
		return User::current()->group()->delete_user();
	}
	static function allowDeleteUserGroup(){
		return User::current()->group()->delete_user_group();
	}
	static function allowDeleteUserGroupAccess(){
		return User::current()->group()->delete_user_group_access();
	}
	static function allowCreateUserGroup(){
		return User::current()->group()->cretaeUserGroup();
	}
	static function allowAppendUserGroupAccess(){
		return User::current()->group()->appendUserGroupAccess();
	}
	static function allowAppendUSerGroupUser(){
		return User::current()->group()->appendUserGroupUser();
	}
	static function allowGetUserGroupId(){
		return User::current()->group()->getUserGroupId();
	}
	static function allowChangeConfig(){
		return User::current()->group()->changeConfig();
	}
}