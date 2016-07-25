<?php
namespace inc\authentication\driver;

use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\error\HeigLevelError;
use inc\head\Head;

class AuthenticationDriver{
	/**
	 * Controle if authentication driver exists
	 * @param string $name name of the driver
	 * @return bool true if the driver exists else false
	 */
	public static function exists(string $name) : bool{
		return file_exists("inc/driver/authentication/".$name."/".$name.".php");
	}
	
	/**
	 * Get driver
	 * @param string $name name of the driver
	 * @return AuthenticationDriverInterface a driver to parform what you want
	 */
	public static function getDriver(string $name) : AuthenticationDriverInterface{
		if(!self::exists($name)){
			throw new HeigLevelError("Unknown authentication driver", "The driver to request: ".$name);
		}
		
		$class = "inc\\driver\\authentication\\".$name."\\".$name."\\AuthenticationDriver";
		return new $class();
	}
	
	public static function getCurrentDriver() : AuthenticationDriverInterface{
		if(!Head::cookie("login_driver") || !self::exists(Head::cookie("login_driver"))){
			throw new HeigLevelError("Unknown driver");
		}
		
		return self::getDriver(Head::cookie("login_driver"));
	}
	
	public static function login() : bool{
		if(!Head::cookie("login_driver") || !self::exists(Head::cookie("login_driver"))){
			return false;
		}
		
		return self::getDriver(Head::cookie("login_driver"))->login();
	}
}