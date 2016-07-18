<?php
namespace inc\interfaces\authentication;

interface AuthenticationDriverInterface{
	function login() : bool;
	function getName() : string;
	function title() : string;
	function enabled() : bool;
}