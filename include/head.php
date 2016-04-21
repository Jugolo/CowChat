<?php

class HeadCache{
   public static $cookie;
}

function setSocketCookie($cookie){
   HeadCache::$cookie = [];//empty the value from the last connection
   foreach(explode(";", $cookie) as $one){
      list($key,$value) = explode("=", $one);
      HeadCache::$cookie[$key] = $value;
   }
}

function cookie($name){
  if(Server::is_cli())
    $use = HeadCache::$cookie;
  else
    $use = $_COOKIE;

  if(empty($use[$name]) || !trim($use[$name]))
    return null;
  return $use[$name];
}
