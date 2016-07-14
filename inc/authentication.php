<?php
namespace inc\authentication;

use inc\head\Head;
use inc\authentication\driver\AuthenticationDriver;
use inc\database\Database;
use inc\error\HeigLevelError;
use inc\setting\Setting;
use inc\error\LoginError;

class Authentication{
	/**
	 * Parfom login check. it is auto login. login and create a account
	 * @return bool true if the user is login or false if not
	 */
	public static function login() : bool{
		//wee try to find out if this is should be a autologin or if it should be a requlare login
		if(self::is_autologin() && self::do_autologin()){
			return true;
		}elseif(Head::post("username") && Head::post("password")){
			if(Head::post("email")){
				return self::do_create(Head::post("username"), Head::post("password"), Head::post("email"));
			}
			
			return self::do_login(Head::post("username"), Head::post("password"));
		}elseif(Head::post("username")){
			return self::do_geaust_login(Head::post("username"));
		}
		
		return false;
	}
	
	/**
	 * Detect if the user should be autologin
	 * @return bool true if autologin is avaribel or false
	 */
	private static function is_autologin() : bool{
		//to be a autologin wee need 2 cookie 'login_method' and 'login_token'
		return Head::cookie("login_driver") !== null && AuthenticationDriver::exists(Head::cookie("login_driver")) && AuthenticationDriver::getDriver(Head::cookie("login_driver"))->enabled();
	}
	
	/**
	 * Login the user as gaust
	 * @param string $username the username the geaust want to use
	 * @return bool true if it okay or false if not
	 */
	private static function do_geaust_login(string $username) : bool{
		
	}
	
	/**
	 * Create a new user. 
	 * @param string $username username and nick
	 * @param string $password password
	 * @param string $email email to use
	 * @return bool true on success or false on fail
	 */
	private static function do_create(string $username, string $password, string $email) : bool{
		return AuthenticationDriver::getDriver(Setting::get("authenticationDriver"))->createAccount($username, $password, $email);
	}
	
	/**
	 * Do a autologin
	 * @return bool true on success and false on fail
	 */
	private static function do_autologin() : bool{
		return AuthenticationDriver::getDriver(Head::cookie("login_driver"))->autoLogin();
	}
	
	/**
	 * Login the user
	 * @param string $username username 
	 * @param string $password password
	 * @return bool true on success and false on fail
	 */
	private static function do_login(string $username, string $password) : bool{
		//wee try to find the user in user table
		$database = Database::getInstance();
		$query = $database->query("SELECT * FROM ".table("user_login")." WHERE `username`=".$database->clean($username));
		$row   = $query->fetch();
		$query->free();
		if($row == null){
			return false;
		}
		
		//controle for the driver
		if(!AuthenticationDriver::exists($row["authentication_driver"])){
			self::on_missing_driver($row["id"]);
			return false;
		}
	}
	
	private static function on_missing_driver(int $uid){
		throw new HeigLevelError("Missing driver for user id: ".$uid);
	}
}