<?php

class Files{
  public static function changeDir($dir){
     set_include_path($dir);
     //to get file_exists to work and in websocket server wee need to change dir
     chdir($dir);
  }

  public static function exists($name){
     return file_exists($name);
  }

  public static function context($name){
    return file_get_contents($name, true);
  }
}
