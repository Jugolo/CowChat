<?php
class Request{
  public static function get(string $key){
    if(empty($_GET[$key]) || !trim($_GET[$key]))
     return null;
    return $_GET[$key];
  }
  
  public static function ip(){
    return $_SERVER['REMOTE_ADDR'];
  }
}
