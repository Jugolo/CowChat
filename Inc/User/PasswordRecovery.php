<?php
namespace Inc\User;

use Request;
use Inc\Random;
use Inc\Mailer;

class PasswordRecovery{
  const RECOVERY_GET = "npRequest";
  
  public static function isRecoveryRequest() : bool{
    if(Request::post("email") && Request::get("recovery")){
      return self::handleRecovery();
    }
    return false;
  }
  
  public static function handleRecovery(){
    global $server;
    //let us try to get the user!
    $db = $server->getDatabase();
    
    $data = $db->query("SELECT `id`, `username`, `email` FROM `".DB_PREFIX."chat_user` WHERE `email`='".$db->query(Request::get("email"))."' AND `status`='N'")->get();
    if(!$data){
      return false;
    }
    $password = Random::string(12);
    $mail = new Mailer();
    $mail->selectTempelate("newpassword");
    $mail->setArg("username", $data["username"]);
    $mail->setArg("password", $data["password"]);
    $mail->send($data["email"]);
    
    $db->query("UPDATE `".DB_PREFIX."chat_user` SET `password`='".$db->clean(sha1($password))."' WHERE `id`='".$data["id"]."'");
    return true;
  }
}
