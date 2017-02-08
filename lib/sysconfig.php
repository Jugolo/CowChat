<?php

class Config{
  private $config = null;
  
  public static function init(){
    if(self::$config !== null){
      exit("Config::init() has been called before. It is only allow to he called once");
    }
    
    $config = include "./lib/config.php";
    $db = $config["db"];
    unset($config["db"]);
    self::$config = $config;
    return $db;
  }
  
  public static function get(string $key) : get{
    if(self::$config === null){
      exit("Init the config system first");
    }
    return self::$config[$key];
  }
}
