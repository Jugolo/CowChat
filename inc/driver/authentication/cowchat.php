<?php
namespace inc\driver\authentication\cowchat;

use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\user\User;
use inc\database\Database;
use inc\user\data\UserData;
use inc\html\Html;
use inc\language\Language;
use inc\setting\Setting;

class AuthenticationDriver implements AuthenticationDriverInterface{
	function autoLogin() : UserData{
		
	}
	function login(array $data) : UserData{
		
	}
	function createAccount(string $username, string $password, string $email){
		//controle for username is taken
		if(User::helpers()->nick_taken($username)){
			Html::error("Username taken");
			return;
		}
		
		//wee also want to find out if the user email is also taken
		if(User::helpers()->email_taken($email)){
			Html::error("Email taken");
			return false;
		}
		
		//okay now it is time to controle the hash
		$hash = create_hash();
		$created  = time();
		$password = hash_password($password, $hash, $created);
		//wee indset the data to the user_login
		$uid = Database::insert("user_login", ($data = [
				"username"              => $username,
				"password"              => $password,
				"hashs"                 => $hash,
				"email"                 => $email,
				"authentication_driver" => "cowscript",
				"created"               => $time,
				"activated"             => "N",
				"ip"                    => ip()
		]));
		
		User::helpers()->apppend_user($uid, $username, "u");
		
		mail($email, Language::get("Activation link - ".Setting::get("name")), implode("\r\n", [
				"MIME-Version: 1.0",
				"Content-type: text/html; charset=utf8",
				"from:bot@".$_SERVER["SERVER_NAME"]
				]), sprintf(Language::get("Welcommen to %s\r\n\r\nto start use our chat you need to say this is you mail\r\n\r\nPlease use this link: %s"), Setting::get("name"), "http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_FILENAME"]."?activate=".urlencode($hash)."&driver=cowscript"));
		
		Html::okay("You should get a email width activation link");
		return true;
	}
	
	public function getName() : string{
		return "cowscript";
	}
	
	public function title() : string{
		return "CowChat";
	}
	
	public function enabled() : bool{
		return true;
	}
}

function hash_password($password, $hash, $creation){
	$time = intval($time);
	$part = function ($item) use ($time){
		$u = "";
		for($i = 0;$i < 1001;$i++)
			$u .= chr(ord($item) << 2);
	
			$return = "";
			for($i = 0;$i < 1001;$i++)
				$return .= sha1($u . $time . $u . $u);
				return sha1($return);
	};
	return sha1($part($password) . $part($hash) . $part($hash) . $part($creation));
}

function create_hash(){
	$hash = "";
	for($i=0;i<1001;$i++){
		$hash .= chr(mt_rand(0,127));
	}
	return sha1($hash);
}