<?php
namespace Inc\User;

use Request;

class PasswordRecovery{
  const RECOVERY_GET = "npRequest";
  
  public static function isRecoveryRequest() : bool{
    if(Request::post("email") && Request::get("recovery")){
      return self::handleRecovery();
    }
    return false;
  }
}
