<?php
namespace inc\user\update;

use inc\head\Head;
use inc\user\User;
use inc\language\Language;

class UserUpdate{
	public static function update(){
		$user = User::getStack()->current();
		if(Head::post("username") && Head::post("username") != $user->getUsername()){
			self::update_username();
		}
		
		if(Head::post("password") && Head::post("repeat_password")){
			self::update_password();
		}
		
		if(Head::post("email")){
			self::update_email();
		}
		
		if(Head::post("nick")){
			self::update_nick();
		}
	}
	
	private static function update_nick(){
		 $user = User::getStack()->current();
		 if(User::helpers()->nick_taken(Head::post("nick"), $user)){
		 	echo Language::get_sprintf("Nick is allready taken")."\r\n";
		 }else{
		 	$user->setNick(Head::post("nick"));
		 }
	}
	
	private static function update_email(){
		$user = User::getStack()->current();
		if($user->getEmail() != Head::post("email")){
			if(User::helpers()->email_taken(Head::post("email"))){
				echo Language::get_sprintf("Email is allready taken")."\r\n";
			}else{
				$user->setEmail(Head::post("email"));
			}
		}
	}
	
	private static function update_username(){
		if(User::helpers()->nick_taken(Head::post("username"), $user)){
			echo Language::get_sprintf("ERROR: Username '%s' is allready taken", Head::post("username"))."\r\n";
		}else{
			User::getStack()->current()->setUsername(Head::post("username"));
		}
	}
	
	private static function update_password(){
		if(Head::post("password") != Head::post("repeat_password")){
			echo Language::get("The two password is not equels")."\r\n";
		}else{
			AuthenticationDriver::getCurrentDriver()->new_password(Head::post("password"));
		}
	}
}