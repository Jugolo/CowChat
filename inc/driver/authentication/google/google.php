<?php
namespace inc\driver\authentication\google\google;

use inc\interfaces\authentication\AuthenticationDriverInterface;

class AuthenticationDriver implements AuthenticationDriverInterface{
	function login() : bool{

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