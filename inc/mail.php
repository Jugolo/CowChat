<?php
namespace inc\mail;

class Mail{
	public static function format_email(string $email) : string{
		if(strpos($email, "@") === false){
			$email = "unknown@".$email;
		}
		
		if(!preg_match("/\.[a-zA-Z\.]$/", $email)){
			$email .= ".com";
		}
		
		return $email;
	}
}