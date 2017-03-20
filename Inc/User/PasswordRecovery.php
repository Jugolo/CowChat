<?php
namespace Inc\User;

use Request;

class PasswordRecovery{
  const RECOVERY_GET = "npRequest";
  
  public static isRecoveryRequest() : bool{
    if($token = Request::get(self::RECOVERY_GET)){
      return self::handleRecovery($token);
    }
    return false;
  }
}
