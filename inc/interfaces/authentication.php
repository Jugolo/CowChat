<?php
namespace inc\interfaces\authentication;

use inc\user\data\UserData;

interface AuthenticationDriverInterface{
	function login() : UserData;
	function auto_login() : UserData;
	function logout();
	function new_password(string $password) : bool;
	function title() : string;
	function enabled() : bool;
}