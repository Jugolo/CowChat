<?php

class Files{
  public static function exists($name){
     return @fopen($name, "r", true) == true;
  }

  public static function context($name){
    return file_get_contents($name, true);
  }

  public static function isFile($name){
     return @file($name, FILE_USE_INCLUDE_PATH) != false;
  }
}

class Dirs{
   public static function isDir($name){
      return is_dir(get_include_path().$name);
   }
}
