<?php

use inc\database\Database;
use inc\system\System;
use inc\authentication\driver\AuthenticationDriver;
use inc\user\data\UserData;
use inc\head\Head;
use inc\driver\authentication\auth_drivers\AuthDrivers;
use inc\tempelate\tempelate\Tempelate;


function ip($ws = null){
	if(!defined("IN_SETUP") && System::is_cli()){
		if(socket_getpeername(($ws !== null ? $ws : User::current()->websocket()), $ip)){
			if($ip == "::1")
				$ip = "127.0.0.1";
				return $ip;
		}
		return null;
	}

	return $_SERVER['REMOTE_ADDR'] == "::1" ? "127.0.0.1" : $_SERVER["REMOTE_ADDR"];
}

function table(string $name) : string{
	return "`" . Database::$prefix . "_" . $name . "`";
}
/**
 * Controle if the user is login. if not return null
 * @return UserData|null
 */
function auto_login(){
	return AuthenticationDriver::autologin();
}

/**
 * Do all work to let the user be login. will not contiune widthout the user is login
 * @return UserData
 */
function login() : UserData{
	if(!Head::cookie("login_driver") || !AuthenticationDriver::exists(Head::cookie("login_driver"))){
		select_auth_driver();
	}
	if($user = auto_login()){
		return $user;
	}else{
		return AuthenticationDriver::login();
	}
}

function select_auth_driver(){
	$auth = new AuthDrivers();
	if($auth->count() == 0){
		exit("The system missing auth driver. Please install some");
	}else if($auth->count() == 1){
		Head::make_cookie("login_driver", $auth->get(0)[0]);
	}else{
		if(Head::get("auth_method")){
			try{
				if($auth->getDriver(Head::get("auth_method"))->enabled()){
					Head::make_cookie("login_driver", Head::get("auth_method"));
					return;
				}else{
					Html::error(Language::get_sprintf("Unknown auth driver %s", Head::get("auth_method")));
				}
			}catch(\inc\exception\AuthDriverNotFound\AuthDriverNotFound $e){
				Html::error(Language::get_sprintf("Unknown auth driver %s", Head::get("auth_method")));
			}
		}
		
		$tempelate = new Tempelate([
				"dir" => "inc/style/",
				"cache" => true
		]);
		$tempelate->add_var("drivers", $auth->toArray());
		$tempelate->exec("auth_chose");
		exit();
	}
}