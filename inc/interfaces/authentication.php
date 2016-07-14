<?php
namespace inc\interfaces\authentication;

use inc\user\data\UserData;

interface AuthenticationDriverInterface{
	function autoLogin() : bool;
	function login(array $data) : UserData;
	function createAccount(string $username, string $password, string $email);
	function getName() : string;
	function title() : string;
	function enabled() : bool;
}