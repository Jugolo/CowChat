<?php
namespace inc\driver\authentication\google;

use inc\interfaces\authentication\AuthenticationDriverInterface;
use inc\user\data\UserData;

class AuthenticationDriver implements AuthenticationDriverInterface{
	function autoLogin() : UserData{

	}
	function login(array $data) : UserData{

	}
	function createAccount(string $username, string $password, string $email){
	}

	public function getName() : string{
		return "google";
	}

	public function title() : string{
		return "Google";
	}
	
	public function enabled() : bool{
		return false;
	}
}