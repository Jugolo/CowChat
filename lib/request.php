<?php
class Request{
  public static function get(string $key){
    if(empty($_GET[$key]) || !trim($_GET[$key]))
     return null;
    return $_GET[$key];
  }
  
  public static function post(string $key){
    if(empty($_POST[$key]) || !trim($_POST[$key])){
      return null;
    }
    return $_POST[$key];
  }
  
  public static function cookie(string $key){
    if(empty($_COOKIE[Config::get("cookie_prefix").$key]) || !trim($_COOKIE[Config::get("cookie_prefix").$key])){
      return null;
    }
    
    return $_COOKIE[Config::get("cookie_prefix").$key];
  }
  
  public static function session(string $key){
    if(empty($_SESSION[Config::get("cookie_prefix").$key])){
      return null;
    }
    return $_SESSION[Config::get("cookie_prefix").$key];
  }
  
  public static function setSession(string $key, $value){
    $_SESSION[Config::get("cookie_prefix").$key] = $value;
  }
  
  public static function unsetSession(string $key){
    if(self::session($key)){
      unset($_SESSION[Config::get("cookie_prefix").$key]);
    }
  }
  
  public static function ip(){
    return $_SERVER['REMOTE_ADDR'];
  }
}
